<?php

// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

namespace Firehed\Container\ConfigGeneratorFixtures;

use RuntimeException;

throw new RuntimeException('Side effect during load');

class ThrowsOnLoad
{
}
