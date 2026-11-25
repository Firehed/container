<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class NewInInitializerParam
{
    public function __construct(
        public readonly NotInContainer $dependency = new NotInContainer(),
    ) {
    }
}
