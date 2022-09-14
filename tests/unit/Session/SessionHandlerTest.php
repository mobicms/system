<?php

declare(strict_types=1);

namespace MobicmsTest\System\Session;

use HttpSoft\Message\Response;
use Mobicms\System\Session\SessionHandler;
use Mobicms\System\Session\SessionInterface;
use Mobicms\Testutils\MysqlTestCase;
use Mobicms\Testutils\SqlDumpLoader;
use Psr\Http\Message\ServerRequestInterface;

class SessionHandlerTest extends MysqlTestCase
{
    public function setUp(): void
    {
        $loader = new SqlDumpLoader(self::getPdo());
        $loader->loadFile('install/sql/system.sql');

        if ($loader->hasErrors()) {
            $this->fail(implode("\n", $loader->getErrors()));
        }
    }

    public function testImplementsSessionInterface(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = new SessionHandler(self::getPdo(), $request);
        $this->assertInstanceOf(SessionInterface::class, $handler);
    }

    public function testHasMethodWithExistingKey(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertTrue($session->has('foo'));
    }

    public function testHasMethodWithNonExistentKey(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertFalse($session->has('bar'));
    }

    public function testGetMethodWithExistingKey(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertSame('test-session', $session->get('foo'));
    }

    public function testGetMethodWithNonExistentKeyReturnNull(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertNull($session->get('bar'));
    }

    public function testGetMethodWithNonExistentKeyReturnDefaultValue(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertSame('mydata', $session->get('bar', 'mydata'));
    }

    public function testUnsetMethod(): void
    {
        $session = $this->initializeSessionWithData();
        $this->assertTrue($session->has('foo'));
        $session->unset('foo');
        $session->unset('bar');
        $this->assertFalse($session->has('foo'));
    }

    public function testSetMethod(): void
    {
        $session = $this->initializeSessionWithData();
        $session->set('foo', 'newdata');
        $session->set('baz', 'bat');
        $this->assertSame('newdata', $session->get('foo'));
        $this->assertSame('bat', $session->get('baz'));
    }

    public function testClearMethod(): void
    {
        $session = $this->initializeSessionWithData();
        $session->set('baz', 'bat');
        $session->clear();
        $this->assertFalse($session->has('foo'));
        $this->assertFalse($session->has('baz'));
    }

    public function testWithExpiredData(): void
    {
        $session = $this->initializeSessionWithData(30000);
        $this->assertFalse($session->has('foo'));
    }

    public function testGarbageCollector(): void
    {
        $this->initializeSessionWithData(30000, true);
        $query = self::getPdo()->query("SELECT * FROM `system__session` WHERE `session_id` = 'testsessionid'");
        $this->assertEquals(0, $query->rowCount());
    }

    public function testSessionPersistence(): void
    {
        /** @var SessionHandler $session */
        $session = $this->initializeSessionWithData();
        $session->set('baz', 'bat');
        $session->persistSession(new Response());

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getCookieParams')
            ->willReturn(['TESTSESSION' => 'testsessionid']);
        $session2 = new SessionHandler(
            self::getPdo(),
            $request,
            ['cookie_name' => 'TESTSESSION', 'lifetime' => 10800,]
        );
        $this->assertEquals('bat', $session2->get('baz'));
    }

    private function initializeSessionWithData(int $modified = 0, bool $gc = false): SessionInterface
    {
        $options = [
            'cookie_name'      => 'TESTSESSION',
            'cookie_domain'    => 'localhost',
            'cookie_path'      => '/',
            'cookie_secure'    => false,
            'cookie_http_only' => true,
            'lifetime'         => 10800,
        ];

        $stmt = self::getPdo()->prepare(
            "INSERT INTO `system__session`
             SET
                 `session_id` = ?,
                 `modified` = ?,
                 `data` = ?"
        );
        $stmt->execute(['testsessionid', time() - $modified, 'a:1:{s:3:"foo";s:12:"test-session";}']);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getCookieParams')
            ->willReturn(['TESTSESSION' => 'testsessionid']);
        return new SessionHandler(self::getPdo(), $request, $options, $gc);
    }
}
