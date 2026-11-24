<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerExceptionInterface;

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
     */
    #[DataProvider('fileErrors')]
    public function testFileError(string $key, array $env): void
    {
        foreach ($env as $name => $value) {
            $_ENV[$name] = $value;
        }
        $container = $this->getContainer();
        self::assertTrue($container->has($key), 'has should be true');
        $this->expectException(ContainerExceptionInterface::class);
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

    /** @return array<string, array{string, array<string, string>}> */
    public static function fileErrors(): array
    {
        $prefix = 'CONTAINER_UNITTEST_FOE_';
        return [
            'fileContents missing' => ['file_contents_missing', []],
            'fileOrEnv not set' => ['file_or_env_not_set', []],
            'fileOrEnv both set' => ['file_or_env_conflict', [
                $prefix . 'CONFLICT' => 'from-env',
                $prefix . 'CONFLICT_FILE' => __DIR__ . '/Fixtures/secret.txt',
            ]],
        ];
    }
}
