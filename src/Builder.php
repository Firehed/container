<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

class Builder implements BuilderInterface
{
    use BuilderTrait;

    /** @var mixed[] */
    private $defs = [];

    /** @var ContainerExceptionInterface[] */
    private $errors = [];

    public function addFile(string $path): void
    {
        $defs = require $path;
        if (!is_array($defs)) {
            throw new UnexpectedValueException(sprintf(
                'File %s did not return an array',
                $path
            ));
        }
        $this->defs = array_merge($this->parseDefs($defs), $this->defs);
    }

    /**
     * @param mixed[] $defs
     * @return mixed[]
     */
    private function parseDefs(array $defs): array
    {
        return iterator_to_array($this->processDefinitions($defs));
    }

    public function build(): TypedContainerInterface
    {
        if ($this->errors !== []) {
            throw $this->errors[0];
        }
        $container = new DevContainer($this->defs, new EnvReader($_ENV));

        return $container;
    }
}
