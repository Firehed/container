<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScalarDefinition::class)]
#[Small]
class ScalarDefinitionTest extends TestCase
{
    public function testObjectIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        new ScalarDefinition(new \stdClass());
    }

    #[DataProvider('validValues')]
    public function testAcceptedValues(mixed $value): void
    {
        $def = new ScalarDefinition($value);
        $container = self::createStub(TypedContainerInterface::class);
        $reader = self::createStub(EnvReader::class);
        self::assertSame($value, $def->resolve($container, $reader));
    }

    /**
     * @return array{mixed}[]
     * @codeCoverageIgnore
     */
    public static function validValues(): array
    {
        return [
            [null],
            [1],
            [3.14],
            [true],
            [false],
            [Fixtures\Environment::PRODUCTION],
            [Fixtures\SomeUnitEnum::Two],
            ['string'],
            [[1, 'asdf', true]],
        ];
    }
}
