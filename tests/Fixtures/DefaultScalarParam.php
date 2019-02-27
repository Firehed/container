<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

class DefaultScalarParam
{
    const DEFAULT_VALUE = 'some string';

    /** @var string */
    private $param;

    public function __construct(string $param = self::DEFAULT_VALUE)
    {
        $this->param = $param;
    }

    public function getParam(): string
    {
        return $this->param;
    }
}
