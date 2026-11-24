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
        return [
            'fileContents' => ['file_contents', [], 'from-file'],
        ];
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function fileErrors(): array
    {
        return [
            'fileContents missing' => ['file_contents_missing', []],
        ];
    }
}
