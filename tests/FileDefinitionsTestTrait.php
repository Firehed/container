<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\DataProvider;

trait FileDefinitionsTestTrait
{
    public function testFileContents(): void
    {
        $container = $this->getContainer();
        self::assertSame('s3cr3t', $container->get('file_contents'), 'Trailing newline should be stripped');
    }

    public function testFileContentsCastsAfterTrimming(): void
    {
        $container = $this->getContainer();
        self::assertSame(8080, $container->get('file_contents_asint'), 'Cast should apply to trimmed contents');
    }

    public function testFileContentsMissingThrows(): void
    {
        $container = $this->getContainer();
        try {
            $container->get('file_contents_missing');
            self::fail('Expected exception not thrown');
        } catch (Exceptions\ValueRetrievalException $e) {
            self::assertInstanceOf(Exceptions\FileNotReadable::class, $e->getPrevious(), 'Missing file should fail');
        }
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('fileOrEnvValues')]
    public function testFileOrEnv(string $key, array $env, mixed $expected): void
    {
        $this->setSecretEnv($env);
        $container = $this->getContainer();
        self::assertSame($expected, $container->get($key), 'Value should come from the expected source');
    }

    /**
     * @return array<string, array{string, array<string, string>, mixed}>
     */
    public static function fileOrEnvValues(): array
    {
        $secret = __DIR__ . '/Fixtures/secret.txt';
        $port = __DIR__ . '/Fixtures/port.txt';
        return [
            'env' => ['file_or_env', ['CONTAINER_UNITTEST_SECRET' => 'from-env'], 'from-env'],
            'file' => ['file_or_env', ['CONTAINER_UNITTEST_SECRET_FILE' => $secret], 's3cr3t'],
            'default' => ['file_or_env_with_default', [], 'default'],
            'file over default' => ['file_or_env_with_default', ['CONTAINER_UNITTEST_SECRET_FILE' => $secret], 's3cr3t'],
            'cast env' => ['file_or_env_asint', ['CONTAINER_UNITTEST_PORT' => '80'], 80],
            'cast file' => ['file_or_env_asint', ['CONTAINER_UNITTEST_PORT_FILE' => $port], 8080],
        ];
    }

    public function testFileOrEnvNotSetThrows(): void
    {
        $this->setSecretEnv([]);
        $container = $this->getContainer();
        $this->expectException(Exceptions\EnvironmentVariableNotSet::class);
        $container->get('file_or_env');
    }

    public function testFileOrEnvBothSetThrows(): void
    {
        $this->setSecretEnv([
            'CONTAINER_UNITTEST_SECRET' => 'from-env',
            'CONTAINER_UNITTEST_SECRET_FILE' => __DIR__ . '/Fixtures/secret.txt',
        ]);
        $container = $this->getContainer();
        $this->expectException(Exceptions\ConflictingEnvironmentVariables::class);
        $container->get('file_or_env_with_default');
    }

    /**
     * @param array<string, string> $env
     */
    private function setSecretEnv(array $env): void
    {
        foreach (['SECRET', 'SECRET_FILE', 'PORT', 'PORT_FILE'] as $suffix) {
            unset($_ENV['CONTAINER_UNITTEST_' . $suffix]);
        }
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}
