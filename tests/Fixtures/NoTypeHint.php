<?php

declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class NoTypeHint
{
    // phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamName
    /** @param mixed $value */
    public function __construct(private $value)
    {
    }
}
