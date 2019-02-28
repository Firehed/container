<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

class LiteralValue implements CodeGeneratorInterface
{
    use NoDependenciesTrait;

    /** @var scalar|array */
    private $literal;

    /** @param scalar|array $literal */
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
