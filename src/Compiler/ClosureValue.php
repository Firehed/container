<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Closure;
use PhpParser\PrettyPrinter\Standard;
use ReflectionFunction;
use SuperClosure\Analyzer\AstAnalyzer;

class ClosureValue implements CodeGeneratorInterface
{
    /** @var Closure */
    private $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function generateCode(string $functionName): string
    {

        $analyzer = new AstAnalyzer();
        $analsis = $analyzer->analyze($this->closure);
        $closureAst = $analsis['ast'];

        $printer = new Standard();
        $formatted = $printer->prettyPrint([$closureAst]);

        // This is a clumsy approach - it copies the raw text of the closure
        // (with use statements correctly expanded) INCLUDING the outer
        // signature, and executes it.
        //
        // An improved version will
        //   a) Use only the body of the closure
        //   b) Replace $arg1 with $this
        // But gettting there will take a fair bit of AST hacking.
        return <<<PHP
protected function $functionName()
{
    return ($formatted)(\$this);
}
PHP;
    }
}
