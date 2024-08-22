<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerExceptionInterface;

trait EnvironmentDefinitionsTestTrait
{
    // Want const, traits can't have them
    private static string $prefix = 'CONTAINER_UNITTEST_';

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

    /**
     * @dataProvider casts
     * @param string $containerKey Access key
     * @param mixed $expected Expected value (post-casting)
     */
    public function testCastingBehavior(string $containerKey, $expected): void
    {
        putenv(self::$prefix . 'ONE_POINT_FIVE=1.5');
        putenv(self::$prefix . 'ONE=1');
        putenv(self::$prefix . 'ZERO=0');
        putenv(self::$prefix . 'TRUE=true');
        putenv(self::$prefix . 'FALSE=false');
        putenv(self::$prefix . 'EMPTY=');
        $container = $this->getContainer();
        $this->assertTrue($container->has($containerKey));
        $this->assertSame($expected, $container->get($containerKey));
    }

    /** @return string[][] */
    public static function envVarsThatAreSet(): array
    {
        return [
            ['env_set'],
            ['env_set_with_default'],
            ['env_set_with_null_default'],
        ];
    }

    /** @return mixed[][] */
    public static function casts(): array
    {
        return [
            ['env_asbool_one', true],
            ['env_asbool_zero', false],
            ['env_asbool_true', true],
            ['env_asbool_false', false],
            ['env_asbool_empty', false],
            ['env_asbool_notset', true],
            ['env_asint_one', 1],
            ['env_asint_zero', 0],
            ['env_asint_notset', 3],
            ['env_asfloat_one_point_five', 1.5],
            ['env_asfloat_one', 1.0],
            ['env_asfloat_zero', 0.0],
            ['env_asfloat_notset', 3.14],
        ];
    }
}
