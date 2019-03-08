<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerExceptionInterface;

trait EnvironmentDefinitionsTestTrait
{
    // Want const, traits can't have them
    private static $prefix = 'CONTAINER_UNITTEST_';

    /**
     * This test demonstrates a counterexample/compile-time value
     */
    public function testRawGetenv(): void
    {
        $rawGetEnvValue = (string)getenv('PWD'); // see CDF3

        $container = $this->getContainer();
        $key = 'env_pwd';
        $this->assertTrue($container->has($key), 'has should be true');
        $value = $container->get($key);
        $this->assertSame($rawGetEnvValue, $value, 'get should return the value');
    }

    /**
     * @dataProvider envVarsThatAreSet
     */
    public function testWrappedEnv(string $key): void
    {
        $unittestEnvVar = md5((string)random_int(0, PHP_INT_MAX));
        putenv("CONTAINER_UNITTEST_SET=$unittestEnvVar");
        $container = $this->getContainer();
        $this->assertTrue($container->has($key), 'has should be true');
        $value = $container->get($key);
        $this->assertSame($unittestEnvVar, $value, 'get should return the value');
    }

    public function testNotSetEnvVarThrowsOnAccess(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('env_not_set'));
        $this->expectException(ContainerExceptionInterface::class);
        $container->get('env_not_set');
    }

    public function testNotSetEnvVarWithDefaultReturnsDefault(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('env_not_set_with_default'));
        $this->assertSame('default', $container->get('env_not_set_with_default'));
    }

    public function testNotSetEnvVarWithNullDefaultReturnsNull(): void
    {
        $container = $this->getContainer();
        $this->assertTrue($container->has('env_not_set_with_null_default'));
        $this->assertNull($container->get('env_not_set_with_null_default'));
    }

    public function envVarsThatAreSet(): array
    {
        return [
            ['env_set'],
            ['env_set_with_default'],
            ['env_set_with_null_default'],
        ];
    }
}
