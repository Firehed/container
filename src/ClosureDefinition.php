<?php

declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use ReflectionFunction;
use UnexpectedValueException;

use function assert;
use function file_get_contents;
use function is_int;
use function sprintf;

class ClosureDefinition implements DefinitionInterface
{
    public function __construct(private Closure $closure)
    {
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function resolve(TypedContainerInterface $container, EnvReader $envReader): mixed
    {
        $rebound = $this->closure->bindTo(null);
        assert($rebound !== null);
        return $rebound($container);
    }

    public function generateCode(): string
    {
        $rf = new ReflectionFunction($this->closure);

        $startLine = $rf->getStartLine();
        assert(is_int($startLine));
        $endLine = $rf->getEndLine();
        assert(is_int($endLine));

        $definingFile = $rf->getFileName();
        assert($definingFile !== false);
        $code = file_get_contents($definingFile);
        assert($code !== false);

        $visitor = new Compiler\ClosureVisitor($startLine, $endLine);

        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromString('8.2'));

        $ast = $parser->parse($code);
        assert($ast !== null);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $astWithResolvedNames = $traverser->traverse($ast);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($astWithResolvedNames);
        $code = $visitor->getCode();
        if ($code === '') {
            throw new UnexpectedValueException('No closure source code found');
        }

        return sprintf(
            'return (%s)($this);',
            $code
        );
    }

    /**
     * @return class-string[]
     *
     * This is not strictly accurate yet, but a correct implementation requires
     * pretty deep AST analysis. This should be treated as a known bug for now.
     */
    public function getDependencies(): array
    {
        return [];
    }
}
