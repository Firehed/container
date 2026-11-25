<?php

// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

namespace Firehed\Container\ConfigGeneratorFixtures\LoadFailure;

use RuntimeException;

// PHP still declares the class below, so only the first load in a process throws.
throw new RuntimeException('Side effect during load');

class ThrowsOnLoad
{
}
