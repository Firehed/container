<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Closure;
use PhpParser\{
    NodeTraverser,
    NodeVisitor\NameResolver,
};
use ReflectionFunction;
use UnexpectedValueException;

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

    /**
     * @throws \PhpParser\Error if the AST cannot be parsed or modified
     */
    public function generateCode(): string
    {
        // This detects the boundaries in the source file that defines the
        // closure, which is used in AST analysis.

        $rf = new ReflectionFunction($this->closure);

        $startLine = $rf->getStartLine();
        assert(is_int($startLine));
        $endLine = $rf->getEndLine();
        assert(is_int($endLine));

        $definingFile = $rf->getFileName();
        assert($definingFile !== false);
        $code = file_get_contents($definingFile);
        assert($code !== false);

        $visitor = new ClosureVisitor($startLine, $endLine);

        $parser = ParserLoader::getParser();

        $ast = $parser->parse($code);
        assert($ast !== null);

        // Before doing anything, apply the built-in name resolution to the
        // entire AST so that any `use` statements (including aliased ones) in
        // the code are expanded.
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $astWithResolvedNames = $traverser->traverse($ast);

        // Travserse with our visitor, which will attempt to extract the code
        // of the closure.
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($astWithResolvedNames);
        $code = $visitor->getCode();
        if ($code === '') {
            throw new UnexpectedValueException('No closure source code found');
        }

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
