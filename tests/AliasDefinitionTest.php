<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AliasDefinition::class)]
#[Small]
class AliasDefinitionTest extends TestCase
{
    public function testResolve(): void
    {
        $def = new AliasDefinition(Fixtures\SessionHandler::class);
        // This doesn't actually need to be a SessionHandler, we just need to
        // test that the right thing comes back.
        $ret = new \stdClass();
        $container = self::createMock(TypedContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with(Fixtures\SessionHandler::class)
            ->willReturn($ret);
        $reader = self::createStub(EnvReader::class);
        self::assertSame($ret, $def->resolve($container, $reader));
    }
}
