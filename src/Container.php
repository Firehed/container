<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private $definitions = [];

    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * @return mixed
     */
    public function get($id)
    {
    }
}
