<?php

declare(strict_types=1);

namespace Firehed\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;

class ValueRetreivalException extends RuntimeException implements ContainerExceptionInterface
{
    /**
     * @var string[]
     */
    private array $idHistory;

    public function __construct(string $id, Throwable $prev)
    {
        parent::__construct('', 0, $prev);
        $this->idHistory = [$id];
        $this->updateMessage();
    }

    private function updateMessage(): void
    {
        $id = $this->idHistory[0];

        if (count($this->idHistory) > 1) {
            $reversed = array_reverse($this->idHistory);
            array_pop($reversed); // Remove entrypoint
            $history = sprintf(' (from %s)', implode(', ', $reversed));
        } else {
            $history = '';
        }

        $prev = $this->getPrevious();
        assert($prev !== null);
        $this->message = sprintf(
            'Error getting "%s"%s: %s',
            $id,
            $history,
            $prev->getMessage()
        );
    }

    public function addId(string $id): void
    {
        $this->idHistory[] = $id;
        $this->updateMessage();
    }
}
