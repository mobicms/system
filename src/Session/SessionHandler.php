<?php

declare(strict_types=1);

namespace Mobicms\System\Session;

use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieManager;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionHandler implements SessionInterface
{
    private PDO $pdo;

    private string $cookieName = 'SESSID';
    private ?string $cookieDomain = null;
    private string $cookiePath = '/';
    private bool $cookieSecure = false;
    private bool $cookieHttpOnly = true;
    private int $lifeTime = 10800;

    private string $id = '';
    private array $data = [];
    private array $originalData = [];

    public function __construct(
        PDO $pdo,
        ServerRequestInterface $request,
        array $options = [],
        bool $gc = false
    ) {
        $this->pdo = $pdo;
        $this->setOptions($options);

        if ($gc) {
            $this->sessionGc();
        }

        $id = $request->getCookieParams()[$this->cookieName] ?? '';

        if (! empty($id)) {
            $this->id = $id;
            $this->data = $this->originalData = $this->sessionRead($id);
        }
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function unset(string $name): void
    {
        unset($this->data[$name]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function persistSession(ResponseInterface $response): ResponseInterface
    {
        if ('' === $this->id && [] === $this->data) {
            return $response;
        }

        $id = '' === $this->id ? bin2hex(random_bytes(16)) : $this->id;
        $this->sessionWrite($id, $this->data);

        $response = $this->sendCookie($id, $response);
        $response = $response->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
        $response = $response->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response->withHeader('Pragma', 'no-cache');
    }

    private function setOptions(array $options): void
    {
        if (isset($options['cookie_name'])) {
            $this->cookieName = (string) $options['cookie_name'];
        }

        if (isset($options['cookie_domain'])) {
            $this->cookieDomain = (string) $options['cookie_domain'];
        }

        if (isset($options['cookie_path'])) {
            $this->cookiePath = (string) $options['cookie_path'];
        }

        if (isset($options['cookie_secure'])) {
            $this->cookieSecure = (bool) $options['cookie_secure'];
        }

        if (isset($options['cookie_http_only'])) {
            $this->cookieHttpOnly = (bool) $options['cookie_http_only'];
        }

        if (isset($options['lifetime'])) {
            $this->lifeTime = (int) $options['lifetime'];
        }
    }

    private function sendCookie(
        string $id,
        ResponseInterface $response
    ): ResponseInterface {
        $manager = new CookieManager();
        $manager->set(
            new Cookie(
                $this->cookieName,
                $id,
                null,
                $this->cookieDomain,
                $this->cookiePath,
                $this->cookieSecure,
                $this->cookieHttpOnly
            )
        );

        return $manager->send($response);
    }

    private function sessionRead(string $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `system__session` WHERE `session_id` = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if (($result = $stmt->fetch()) !== false) {
            if ($result['modified'] > time() - $this->lifeTime) {
                return unserialize($result['data'], ['allowed_classes' => false]);
            }

            $this->sessionDestroy($id);
        }

        return [];
    }

    private function sessionWrite(string $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `system__session`
            (`session_id`, `modified`, `data`)
            VALUES(:id, :modified, :data)
            ON DUPLICATE KEY UPDATE
            `modified` = :modified,
            `data` = :data'
        );

        $stmt->bindParam(':id', $id);
        $stmt->bindValue(':modified', time(), PDO::PARAM_INT);
        $stmt->bindValue(':data', serialize($data));
        $stmt->execute();
    }

    private function sessionDestroy(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `system__session` WHERE `session_id` = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    private function sessionGc(): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `system__session` WHERE `modified` < :duration');
        $stmt->bindValue(':duration', time() - $this->lifeTime, PDO::PARAM_INT);
        $stmt->execute();
    }
}
