<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
#[Small]
class EnvTest extends TestCase
{

    public static function envMatrix(): array
    {
        $out = [];
        foreach (DotenvMode::cases() as $dotenv) {
            foreach (Env::cases() as $env) {
                foreach (Override::cases() as $override) {
                    foreach (VariablesOrder::cases() as $var) {
                        $out[] = [$dotenv, $env, $override, $var];
                    }
                }
            }
        }
        return $out;
    }

    #[DataProvider('envMatrix')]
    public function testEnvReading(
        DotenvMode $dotenv,
        Env $env,
        Override $override,
        VariablesOrder $order,
    ): void {

        $cmd = sprintf(
            'ENV=%s %s php -d variables_order=%s %s %s 2>&1',
            $env->value,
            $override->value,
            $order->value,
            escapeshellarg(__DIR__ . '/readenv.php'),
            $dotenv->value,
        );
        // echo "$cmd\n";
        exec($cmd, $output, $code);

        $outputText = implode("\n", $output);
        // echo $outputText;
        self::assertSame(0, $code, 'Command exited with error');
        // var_dump($outputText);

        if ($override === Override::None) {
            self::assertSame('dotenv', $outputText);
        } else {
            self::assertSame('shell', $outputText);
        }

        // run with `php -d $order->value`
        // inspect output
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
    case IncludeEnv = 'EGP';
    case ExcludeEnv = 'GP';
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
