<?php

declare(strict_types=1);

namespace Firehed\Container;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoDetect::class)]
#[Small]
class AutoDetectTest extends TestCase
{
    public function testEmptyDirectoryIsError(): void
    {
        self::expectException(LogicException::class);
        AutoDetect::from('');
    }
}
