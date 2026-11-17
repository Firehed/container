<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * @phpstan-import-type Cast from CastableValueInterface
 */
#[CoversClass(Caster::class)]
class CasterTest extends TestCase
{
    /**
     * @param Cast $cast
     */
    #[DataProvider('casts')]
    public function testCast(?string $value, string $cast, mixed $expected): void
    {
        self::assertSame($expected, (new Caster())->cast($value, $cast), 'Value should be cast');
    }

    /**
     * @return array<string, array{
     *   ?string,
     *   Cast,
     *   mixed,
     * }>
     */
    public static function casts(): array
    {
        return [
            'none' => ['abc', CastableValueInterface::CAST_NONE, 'abc'],
            'none null' => [null, CastableValueInterface::CAST_NONE, null],
            'bool true' => ['true', CastableValueInterface::CAST_BOOL, true],
            'bool 1' => ['1', CastableValueInterface::CAST_BOOL, true],
            'bool false' => ['false', CastableValueInterface::CAST_BOOL, false],
            'bool 0' => ['0', CastableValueInterface::CAST_BOOL, false],
            'bool empty' => ['', CastableValueInterface::CAST_BOOL, false],
            'bool null' => [null, CastableValueInterface::CAST_BOOL, false],
            'int' => ['-5', CastableValueInterface::CAST_INT, -5],
            'int null' => [null, CastableValueInterface::CAST_INT, 0],
            'float' => ['3.14', CastableValueInterface::CAST_FLOAT, 3.14],
            'float null' => [null, CastableValueInterface::CAST_FLOAT, 0.0],
            'enum' => ['testing', Fixtures\Environment::class, Fixtures\Environment::TESTING],
        ];
    }

    public function testInvalidBoolThrows(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        (new Caster())->cast('invalid', CastableValueInterface::CAST_BOOL);
    }

    public function testEnumNullThrows(): void
    {
        $this->expectException(\TypeError::class);
        (new Caster())->cast(null, Fixtures\Environment::class);
    }

    public function testEnumInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        (new Caster())->cast('invalid', Fixtures\Environment::class);
    }

    public function testNoCastPassesAnyValueThrough(): void
    {
        $value = new \stdClass();
        self::assertSame($value, (new Caster())->cast($value, CastableValueInterface::CAST_NONE), 'No cast means no checks');
    }

    public function testNonStringValueThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new Caster())->cast(123, CastableValueInterface::CAST_INT);
    }
}
