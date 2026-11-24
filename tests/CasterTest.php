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
     * @param ?Cast $cast
     */
    #[DataProvider('casts')]
    public function testCast(?string $value, CastType|string|null $cast, mixed $expected): void
    {
        self::assertSame($expected, (new Caster())->cast($value, $cast), 'Value should be cast');
    }

    /**
     * @return array<string, array{
     *   ?string,
     *   ?Cast,
     *   mixed,
     * }>
     */
    public static function casts(): array
    {
        return [
            'none' => ['abc', null, 'abc'],
            'none null' => [null, null, null],
            'bool true' => ['true', CastType::Bool, true],
            'bool 1' => ['1', CastType::Bool, true],
            'bool false' => ['false', CastType::Bool, false],
            'bool 0' => ['0', CastType::Bool, false],
            'bool empty' => ['', CastType::Bool, false],
            'bool null' => [null, CastType::Bool, false],
            'int' => ['42', CastType::Int, 42],
            'int zero' => ['0', CastType::Int, 0],
            'int negative' => ['-5', CastType::Int, -5],
            'int null' => [null, CastType::Int, 0],
            'float' => ['3.14', CastType::Float, 3.14],
            'float zero' => ['0.0', CastType::Float, 0.0],
            'float negative' => ['-2.5', CastType::Float, -2.5],
            'float null' => [null, CastType::Float, 0.0],
            'enum' => ['testing', Fixtures\Environment::class, Fixtures\Environment::TESTING],
        ];
    }

    public function testInvalidBoolThrows(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        (new Caster())->cast('invalid', CastType::Bool);
    }

    public function testEnumNullThrows(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type string, null given');
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
        $result = (new Caster())->cast($value, null);
        self::assertSame($value, $result, 'No cast means no checks');
    }

    public function testNonStringValueThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new Caster())->cast(123, CastType::Int);
    }
}
