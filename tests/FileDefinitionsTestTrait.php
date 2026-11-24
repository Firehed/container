<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

trait FileDefinitionsTestTrait
{
    /**
     * @param array<string, string> $env
     */
    #[DataProvider('fileValues')]
    public function testFileValue(string $key, array $env, mixed $expected): void
    {
        foreach ($env as $name => $value) {
            $_ENV[$name] = $value;
        }
        $container = $this->getContainer();
        self::assertTrue($container->has($key), 'has should be true');
        self::assertSame($expected, $container->get($key), 'get should return the value');
    }

    /**
     * @param array<string, string> $env
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('fileErrors')]
    public function testFileError(string $key, array $env, string $exception): void
    {
        foreach ($env as $name => $value) {
            $_ENV[$name] = $value;
        }
        $container = $this->getContainer();
        self::assertTrue($container->has($key), 'has should be true');
        $this->expectException($exception);
        $container->get($key);
    }

    /** @return array<string, array{string, array<string, string>, mixed}> */
    public static function fileValues(): array
    {
        $prefix = 'CONTAINER_UNITTEST_FOE_';
        $secret = __DIR__ . '/Fixtures/secret.txt';
        return [
            'fileContents' => ['file_contents', [], 'from-file'],
            'fileOrEnv via env' => ['file_or_env_env', [$prefix . 'ENV' => 'from-env'], 'from-env'],
            'fileOrEnv via file' => ['file_or_env_file', [$prefix . 'FILE_FILE' => $secret], 'from-file'],
            'fileOrEnv default' => ['file_or_env_default', [], 'default'],
            'fileOrEnv null default' => ['file_or_env_null_default', [], null],
        ];
    }

    /** @return array<string, array{string, array<string, string>, class-string<Throwable>}> */
    public static function fileErrors(): array
    {
        $prefix = 'CONTAINER_UNITTEST_FOE_';
        return [
            'fileContents missing' => ['file_contents_missing', [], Exceptions\FileNotReadable::class],
            'fileOrEnv not set' => ['file_or_env_not_set', [], Exceptions\EnvironmentVariableNotSet::class],
            'fileOrEnv both set' => [
                'file_or_env_conflict',
                [
                    $prefix . 'CONFLICT' => 'from-env',
                    $prefix . 'CONFLICT_FILE' => __DIR__ . '/Fixtures/secret.txt',
                ],
                Exceptions\ConflictingEnvironmentVariables::class,
            ],
        ];
    }
}
