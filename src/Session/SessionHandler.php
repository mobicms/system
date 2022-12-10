<?php

declare(strict_types=1);

namespace Mobicms\Session;

use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieManagerInterface;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionHandler implements SessionInterface
{
    private PDO $pdo;
    private CookieManagerInterface $cookieManager;

    private string $cookieName = 'SESSID';
    private ?string $cookieDomain = null;
    private string $cookiePath = '/';
    private bool $cookieSecure = false;
    private bool $cookieHttpOnly = true;
    private int $lifeTime = 10800;

    private string $sessionId = '';
    private array $data = [];

    public function __construct(PDO $pdo, CookieManagerInterface $cookieManager, array $options = [])
    {
        $this->pdo = $pdo;
        $this->cookieManager = $cookieManager;
        $this->resolveOptions($options);
    }

    public function startSession(ServerRequestInterface $request): void
    {
        $id = trim((string) ($request->getCookieParams()[$this->cookieName] ?? ''));

        if (! empty($id) && strlen($id) == 32) {
            $this->sessionId = $id;

            $stmt = $this->pdo->prepare('SELECT * FROM `system__session` WHERE `session_id` = :id');
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            /** @var array|false $result */
            $result = $stmt->fetch();

            if ($result !== false && $result['modified'] > time() - $this->lifeTime) {
                $this->data = (array) unserialize((string) $result['data'], ['allowed_classes' => false]);
            }
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
        if ('' === $this->sessionId && [] === $this->data) {
            return $response;
        }

        $id = '' === $this->sessionId ? bin2hex(random_bytes(16)) : $this->sessionId;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `system__session`
            (`session_id`, `modified`, `data`)
            VALUES(:id, :modified, :data)
            ON DUPLICATE KEY UPDATE
            `modified` = :modified,
            `data` = :data'
        );

        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':modified', time(), PDO::PARAM_INT);
        $stmt->bindValue(':data', serialize($this->data));
        $stmt->execute();

        return $this->sendCookies($id, $response);
    }

    public function garbageCollector(): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `system__session` WHERE `modified` < :duration');
        $stmt->bindValue(':duration', time() - $this->lifeTime, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function sendCookies(string $id, ResponseInterface $response): ResponseInterface
    {
        $this->cookieManager->set(
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

        $response = $response->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
        $response = $response->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $response->withHeader('Pragma', 'no-cache');
    }

    private function resolveOptions(array $options): void
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
}
