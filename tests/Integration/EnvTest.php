<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
#[Medium]
class EnvTest extends TestCase
{
    public function tearDown(): void
    {
        // Destroy compiled container file
        $file = __DIR__ . '/vendor/compiledConfig.php';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Mode=none, all fail
    public static function noDotenvMatrix(): array
    {
        $out = [];
        foreach (Env::cases() as $env) {
            foreach (Override::cases() as $override) {
                foreach (VariablesOrder::cases() as $var) {
                    self::buildCase(DotenvMode::None, $env, $override, $var, $out);
                }
            }
        }
        return $out;
    }

    #[DataProvider('noDotenvMatrix')]
    public function testNoDotenv(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
    ): void {
        $cmd = self::buildCommand($dotenv, $env, $override, $order);
        exec($cmd, $output, $code);

        if ($override === Override::None) {
            self::assertSame(2, $code);
        } else {
            self::assertSame(0, $code);
            self::assertSame('shell', implode("\n", $output));
        }
    }

    // Mode is mutable: dotenv value always wins
    public static function mutableMatrix(): array
    {
        $out = [];
        foreach ([DotenvMode::Mutable, DotenvMode::UnsafeMutable] as $dotenv) {
            foreach (Env::cases() as $env) {
                foreach (Override::cases() as $override) {
                    foreach (VariablesOrder::cases() as $var) {
                        self::buildCase($dotenv, $env, $override, $var, $out);
                    }
                }
            }
        }
        return $out;
    }

    #[DataProvider('mutableMatrix')]
    public function testMutableReading(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
    ): void {
        $cmd = self::buildCommand($dotenv, $env, $override, $order);
        exec($cmd, $output, $code);

        $outputText = implode("\n", $output);
        self::assertSame(0, $code, 'Command exited with error: ' . $outputText);
        self::assertSame('dotenv', $outputText);
    }


    // Mode is immutable: override value wins
    public static function immutableMatrix(): array
    {
        $out = [];
        foreach ([DotenvMode::Immutable, DotenvMode::UnsafeImmutable] as $dotenv) {
            foreach (Env::cases() as $env) {
                foreach (Override::cases() as $override) {
                    foreach (VariablesOrder::cases() as $var) {
                        self::buildCase($dotenv, $env, $override, $var, $out);
                    }
                }
            }
        }
        return $out;
    }

    #[DataProvider('immutableMatrix')]
    public function testImmutableReading(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
    ): void {
        $cmd = self::buildCommand($dotenv, $env, $override, $order);
        exec($cmd, $output, $code);

        $outputText = implode("\n", $output);
        self::assertSame(0, $code, 'Command exited with error: ' . $outputText);

        if ($override === Override::None) {
            self::assertSame('dotenv', $outputText);
        } else {
            self::assertSame('shell', $outputText);
        }
    }

    private static function buildCase(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
        array &$out,
    ): void {
        $key = sprintf(
            '%s %s %s %s',
            $dotenv->value,
            $env->value,
            $override->value,
            $order->value,
        );
        $out[$key] = [$dotenv, $env, $override, $order];
    }


    private static function buildCommand(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
    ): string {
        return sprintf(
            'ENV=%s %s php -d variables_order=%s %s %s 2>&1',
            $env->value,
            $override->value,
            $order->value,
            escapeshellarg(__DIR__ . '/readenv.php'),
            $dotenv->value,
        );
    }

}

enum DotenvMode: string
{
    case None = 'none';
    case Immutable = 'immutable';
    case Mutable = 'mutable';
    case UnsafeMutable = 'unsafe_mutable';
    case UnsafeImmutable = 'unsafe_immutable';
}

enum VariablesOrder: string
{
    case EnvOnly = 'EGP';
    case EnvAndServer = 'EGPS';
    case ServerOnly = 'GPS';
    case Neither = 'GP';
}

enum Env: string
{
    case Dev = 'dev';
    case Other = 'compiled';
}

enum Override: string
{
    case None = '';
    case Shell = 'FOO=shell';
}
