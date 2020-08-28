<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Closure;
use Opis\Closure\ReflectionClosure;
use ReflectionFunction;

class ClosureValue implements CodeGeneratorInterface
{
    // This is not strictly accurate yet, but a correct implememntation
    // requires pretty deep AST analysis. This should be treated as a known bug
    // for now.
    use NoDependenciesTrait;

    /** @var Closure */
    private $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function generateCode(): string
    {
        $sc = new ReflectionClosure($this->closure);
        $code = $sc->getCode();

        // This is a clumsy approach - it copies the raw text of the closure
        // (with use statements correctly expanded) INCLUDING the outer
        // signature, and executes it.
        //
        // An improved version will
        //   a) Use only the body of the closure
        //   b) Replace $arg1 with $this
        // But gettting there will take a fair bit of AST hacking.
        return sprintf(
            'return (%s)($this);',
            $code
        );
    }
}
