<?php

declare(strict_types=1);

namespace Firehed\Container\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[Small]
class EnvTest extends TestCase
{

    public static function envMatrix(): array
    {
        $out = [];
        foreach (DotenvMode::cases() as $dotenv) {
            foreach (VariablesOrder::cases() as $var) {
                $out[] = [$dotenv, $var];
            }
        }
        return $out;
    }

    #[DataProvider('envMatrix')]
    public function testEnvReading(DotenvMode $dotenv, VariablesOrder $order): void
    {

        // run with `php -d $order->value`
        // inspect output
    }

}

enum DotenvMode {
  case None;
  case Immutable;
  case Mutable;
  case UnsafeMutable;
  case UnsafeImmutable;
}
enum VariablesOrder: string {
  case IncludeEnv = 'EGP';
  case ExcludeEnv = 'GP';
}
