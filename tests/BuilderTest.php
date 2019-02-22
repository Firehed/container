<?php
declare(strict_types=1);

namespace Firehed\Container;

use DateTime;
use Psr\Container\ContainerInterface;
use SessionHandlerInterface;
use SessionIdInterface;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface */
    private $container;

    public function setUp(): void
    {
        $defFile1 = __DIR__ . '/ContainerTestDefinitionFile1.php';
        $defFile2 = __DIR__ . '/ContainerTestDefinitionFile2.php';
        // $def = include $defFile;

        $builder = new Builder();
        $builder->addFile($defFile1);
        $builder->addFile($defFile2);
        $this->container = $builder->build();
    }

    public function testAutowiredDefinition()
    {
        $this->assertTrue($this->container->has(Fixtures\SessionId::class));
        $sessionId = $this->container->get(Fixtures\SessionId::class);
        assert($sessionId instanceof Fixtures\SessionId);
    }

    public function testAutowiredDefinitionWithConstuctorArg()
    {
        $this->assertTrue($this->container->has(Fixtures\SessionHandler::class));
        $sh = $this->container->get(Fixtures\SessionHandler::class);
        assert($sh instanceof Fixtures\SessionHandler);
        assert($sh instanceof SessionHandlerInterface);
    }

    public function testMultipleGetCallsToSameObjectReturnInstance()
    {
        $this->assertTrue($this->container->has(Fixtures\SessionId::class));
        $sessionId1 = $this->container->get(Fixtures\SessionId::class);
        $sessionId2 = $this->container->get(Fixtures\SessionId::class);
        $sessionId3 = $this->container->get(Fixtures\SessionId::class);
        assert($sessionId1 instanceof Fixtures\SessionId);
        assert($sessionId2 instanceof Fixtures\SessionId);
        assert($sessionId3 instanceof Fixtures\SessionId);
        $this->assertSame($sessionId1, $sessionId2);
        $this->assertSame($sessionId1, $sessionId3);
        $this->assertSame($sessionId2, $sessionId3);
    }

    public function testInterfaceMapping()
    {
        $this->assertTrue($this->container->has(SessionIdInterface::class));
        $sid = $this->container->get(SessionIdInterface::class);
        assert($sid instanceof SessionIdInterface);
        assert($sid instanceof Fixtures\SessionId);
    }

    public function testFirstCallToFactory()
    {
        $this->assertTrue($this->container->has(DateTime::class));
        $dt = $this->container->get(DateTime::class);
        assert($dt instanceof DateTime);
    }

    public function testMultipleCallsToFactoryWithBodyReturnDifferentObjects()
    {
        $this->assertTrue($this->container->has(DateTime::class));
        $dt1 = $this->container->get(DateTime::class);
        $dt2 = $this->container->get(DateTime::class);
        $dt3 = $this->container->get(DateTime::class);
        assert($dt1 instanceof DateTime);
        assert($dt2 instanceof DateTime);
        assert($dt3 instanceof DateTime);
        $this->assertNotSame($dt1, $dt2);
        $this->assertNotSame($dt1, $dt3);
        $this->assertNotSame($dt2, $dt3);
    }

    public function testMultipleCallsToFactoryWithNoBodyReturnDifferentObjects()
    {
        $this->assertTrue($this->container->has(Fixtures\NoConstructorFactory::class));
        $ncf1 = $this->container->get(Fixtures\NoConstructorFactory::class);
        $ncf2 = $this->container->get(Fixtures\NoConstructorFactory::class);
        $ncf3 = $this->container->get(Fixtures\NoConstructorFactory::class);
        assert($ncf1 instanceof Fixtures\NoConstructorFactory);
        assert($ncf2 instanceof Fixtures\NoConstructorFactory);
        assert($ncf3 instanceof Fixtures\NoConstructorFactory);
        $this->assertNotSame($ncf1, $ncf2);
        $this->assertNotSame($ncf1, $ncf3);
        $this->assertNotSame($ncf2, $ncf3);
    }
}
