<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Container\ContainerExceptionInterface;
use Throwable;

class DevContainer implements TypedContainerInterface
{
    use TypedContainerTrait;

    /** @var mixed[] */
    private $evaluated = [];

    /** @param mixed[] $definitions */
    public function __construct(private array $definitions, private EnvReader $envReader)
    {
    }

    /** @return array{ids: list<string>} */
    public function __debugInfo(): array
    {
        $ids = array_keys($this->definitions);
        sort($ids);
        return ['ids' => $ids];
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    public function get($id)
    {
        try {
            return $this->doGet($id);
        } catch (Throwable $e) {
            if ($e instanceof Exceptions\ValueRetrievalException) {
                $e->addId($id);
            }
            if ($e instanceof ContainerExceptionInterface) {
                // If it's a known (i.e. internal) exception, rethrow it
                throw $e;
            }
            // Repackage the error into something with a more helpful message
            throw new Exceptions\ValueRetrievalException($id, $e);
        }
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function doGet($id)
    {
        if (array_key_exists($id, $this->evaluated)) {
            return $this->evaluated[$id];
        }

        if (!$this->has($id)) {
            throw new Exceptions\NotFound($id);
        }

        $def = $this->definitions[$id];

        if ($def instanceof DefinitionInterface) {
            $result = $def->resolve($this, $this->envReader);
            if ($def->isCacheable()) {
                $this->evaluated[$id] = $result;
            }
            return $result;
        }

        return $def;
    }
}
