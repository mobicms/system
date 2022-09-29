<?php

declare(strict_types=1);

namespace MobicmsTest\Session;

use HttpSoft\Cookie\CookieManagerInterface;
use HttpSoft\Message\Response;
use Mobicms\Session\SessionHandler;
use Mobicms\Session\SessionInterface;
use Mobicms\Testutils\MysqlTestCase;
use Mobicms\Testutils\SqlDumpLoader;
use Psr\Http\Message\ServerRequestInterface;

class SessionHandlerTest extends MysqlTestCase
{
    private SessionHandler $session;
    private ServerRequestInterface $request;
    private array $options = [
        'cookie_name'      => 'TESTSESSION',
        'cookie_domain'    => 'localhost',
        'cookie_path'      => '/',
        'cookie_secure'    => false,
        'cookie_http_only' => true,
        'lifetime'         => 10800,
    ];

    public function setUp(): void
    {
        $loader = new SqlDumpLoader(self::getPdo());
        $loader->loadFile('install/sql/system.sql');

        if ($loader->hasErrors()) {
            $this->fail(implode("\n", $loader->getErrors()));
        }

        $this->session = new SessionHandler(
            self::getPdo(),
            $this->createMock(CookieManagerInterface::class),
            $this->options
        );
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->request
            ->method('getCookieParams')
            ->willReturn(['TESTSESSION' => 'ssssssssssssssssssssssssssssssss']);
    }

    public function testImplementsSessionInterface(): void
    {
        $this->assertInstanceOf(SessionInterface::class, $this->session);
    }

    public function testHasMethodWithExistingKey(): void
    {
        $this->initializeSessionWithData();
        $this->assertTrue($this->session->has('foo'));
    }

    public function testHasMethodWithNonExistentKey(): void
    {
        $this->initializeSessionWithData();
        $this->assertFalse($this->session->has('bar'));
    }

    public function testGetMethodWithExistingKey(): void
    {
        $this->initializeSessionWithData();
        $this->assertSame('test-session', $this->session->get('foo'));
    }

    public function testGetMethodWithNonExistentKeyReturnNull(): void
    {
        $this->initializeSessionWithData();
        $this->assertNull($this->session->get('bar'));
    }

    public function testGetMethodWithNonExistentKeyReturnDefaultValue(): void
    {
        $this->initializeSessionWithData();
        $this->assertSame('mydata', $this->session->get('bar', 'mydata'));
    }

    public function testUnsetMethod(): void
    {
        $this->initializeSessionWithData();
        $this->assertTrue($this->session->has('foo'));
        $this->session->unset('foo');
        $this->session->unset('bar');
        $this->assertFalse($this->session->has('foo'));
    }

    public function testSetMethod(): void
    {
        $this->initializeSessionWithData();
        $this->session->set('foo', 'newdata');
        $this->session->set('baz', 'bat');
        $this->assertSame('newdata', $this->session->get('foo'));
        $this->assertSame('bat', $this->session->get('baz'));
    }

    public function testClearMethod(): void
    {
        $this->initializeSessionWithData();
        $this->session->set('baz', 'bat');
        $this->session->clear();
        $this->assertFalse($this->session->has('foo'));
        $this->assertFalse($this->session->has('baz'));
    }

    public function testWithExpiredData(): void
    {
        $this->initializeSessionWithData(30000);
        $this->assertFalse($this->session->has('foo'));
    }

    public function testGarbageCollector(): void
    {
        $this->initializeSessionWithData(30000);
        $this->session->garbageCollector();
        $query = self::getPdo()->query(
            "SELECT * FROM `system__session` WHERE `session_id` = 'ssssssssssssssssssssssssssssssss'"
        );
        $this->assertEquals(0, $query->rowCount());
    }

    public function testPersistenceWithExistingSessionId(): void
    {
        $this->initializeSessionWithData();
        $this->session->set('baz', 'bat');
        $this->session->persistSession(new Response());

        $session2 = new SessionHandler(
            self::getPdo(),
            $this->createMock(CookieManagerInterface::class),
            $this->options
        );
        $session2->startSession($this->request);
        $this->assertEquals('bat', $session2->get('baz'));
    }

    public function testPeresistenceGenerateNewsessionId(): void
    {
        $this->session->startSession($this->createMock(ServerRequestInterface::class));
        $this->session->set('baz', 'bat');
        $this->session->persistSession(new Response());

        $id = self::getPdo()->query('SELECT * FROM `system__session`')->fetchColumn();
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getCookieParams')
            ->willReturn(['TESTSESSION' => $id]);

        $newSession = new SessionHandler(
            self::getPdo(),
            $this->createMock(CookieManagerInterface::class),
            $this->options
        );
        $newSession->startSession($request);
        $this->assertEquals('bat', $newSession->get('baz'));
    }

    public function testPersistenceIfNoIdAndNoData(): void
    {
        $this->session->startSession($this->createMock(ServerRequestInterface::class));
        $this->session->persistSession(new Response());
        $query = self::getPdo()->query('SELECT * FROM `system__session`');
        $this->assertEquals(0, $query->rowCount());
    }

    private function initializeSessionWithData(int $modified = 0): void
    {
        self::getPdo()->prepare(
            "INSERT INTO `system__session`
             SET
                 `session_id` = ?,
                 `modified` = ?,
                 `data` = ?"
        )->execute(['ssssssssssssssssssssssssssssssss', time() - $modified, 'a:1:{s:3:"foo";s:12:"test-session";}']);
        $this->session->startSession($this->request);
    }
}
