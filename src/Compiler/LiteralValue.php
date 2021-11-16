<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

/**
 * @phpstan-type ScalarLike null|scalar|scalar[]|\UnitEnum
 */
class LiteralValue implements CodeGeneratorInterface
{
    use NoDependenciesTrait;

    /** @var ScalarLike */
    private $literal;

    /** @param ScalarLike $literal */
    public function __construct($literal)
    {
        $this->literal = $literal;
    }

    public function generateCode(): string
    {
        return sprintf(
            'return %s;',
            var_export($this->literal, true)
        );
    }
}
