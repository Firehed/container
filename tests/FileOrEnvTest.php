<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(FileOrEnv::class)]
#[CoversClass(Exceptions\ConflictingEnvironmentVariables::class)]
class FileOrEnvTest extends TestCase
{
    use GeneratedCodeTestTrait;

    private const SECRET = __DIR__ . '/Fixtures/secret.txt';

    public function testIsCacheable(): void
    {
        self::assertTrue((new FileOrEnv('FOO'))->isCacheable(), 'Value should only be read once');
    }

    public function testHasNoDependencies(): void
    {
        self::assertSame([], (new FileOrEnv('FOO'))->getDependencies(), 'No other container entries are needed');
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('values')]
    public function testResolve(FileOrEnv $def, array $env, ?string $expected): void
    {
        $value = $def->resolve(self::createStub(TypedContainerInterface::class), $this->envReader($env));
        self::assertSame($expected, $value, 'Resolved value is wrong');
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('values')]
    public function testGeneratedCode(FileOrEnv $def, array $env, ?string $expected): void
    {
        $value = $this->evaluateGeneratedCode($def->generateCode(), $this->envReader($env));
        self::assertSame($expected, $value, 'Compiled code should behave the same as resolve()');
    }

    /**
     * @param array<string, string> $env
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('errors')]
    public function testResolveThrows(FileOrEnv $def, array $env, string $exception): void
    {
        $this->expectException($exception);
        $def->resolve(self::createStub(TypedContainerInterface::class), $this->envReader($env));
    }

    /**
     * @param array<string, string> $env
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('errors')]
    public function testGeneratedCodeThrows(FileOrEnv $def, array $env, string $exception): void
    {
        $this->expectException($exception);
        $this->evaluateGeneratedCode($def->generateCode(), $this->envReader($env));
    }

    /** @return array<string, array{FileOrEnv, array<string, string>, ?string}> */
    public static function values(): array
    {
        return [
            'env only' => [new FileOrEnv('FOO'), ['FOO' => 'from-env'], 'from-env'],
            'file only' => [new FileOrEnv('FOO'), ['FOO_FILE' => self::SECRET], 'from-file'],
            'neither, default' => [new FileOrEnv('FOO', 'fallback'), [], 'fallback'],
            'neither, null default' => [new FileOrEnv('FOO', null), [], null],
            'env wins over default' => [new FileOrEnv('FOO', 'fallback'), ['FOO' => 'from-env'], 'from-env'],
            'file wins over default' => [new FileOrEnv('FOO', 'fallback'), ['FOO_FILE' => self::SECRET], 'from-file'],
        ];
    }

    /** @return array<string, array{FileOrEnv, array<string, string>, class-string<Throwable>}> */
    public static function errors(): array
    {
        return [
            'both set' => [
                new FileOrEnv('FOO'),
                ['FOO' => 'from-env', 'FOO_FILE' => self::SECRET],
                Exceptions\ConflictingEnvironmentVariables::class,
            ],
            'missing file' => [
                new FileOrEnv('FOO'),
                ['FOO_FILE' => self::SECRET . '-missing'],
                Exceptions\FileNotReadable::class,
            ],
            'neither, no default' => [
                new FileOrEnv('FOO'),
                [],
                Exceptions\EnvironmentVariableNotSet::class,
            ],
        ];
    }

    /**
     * @param array<string, string> $env
     */
    private function envReader(array $env): EnvReader
    {
        $reader = self::createStub(EnvReader::class);
        $reader->method('read')->willReturnCallback(fn (string $key): ?string => $env[$key] ?? null);
        return $reader;
    }
}
