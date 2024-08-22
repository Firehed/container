<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class DefaultScalarParam
{
    const DEFAULT_VALUE = 'some string';

    public function __construct(private string $param = self::DEFAULT_VALUE)
    {
    }

    public function getParam(): string
    {
        return $this->param;
    }
}
