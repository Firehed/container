<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use PhpParser\{
    Node,
    NodeTraverser,
    NodeVisitorAbstract,
    PrettyPrinter\Standard,
};

/**
 * Detects the "correct" closure in the AST (by looking at line number) and
 * makes the corresponding source code available.
 *
 * @internal
 */
class ClosureVisitor extends NodeVisitorAbstract
{
    private int $startLine;
    private int $endLine;
    private string $code = '';

    public function __construct(int $startLine, int $endLine)
    {
        $this->startLine = $startLine;
        $this->endLine = $endLine;
    }

    public function enterNode(Node $node)
    {
        // Ignore anything that's not a closure
        if ($node instanceof Node\Expr\Closure) {
            if ($this->extractClosureCode($node)) {
                return NodeTraverser::STOP_TRAVERSAL;
            }
        }
        if ($node instanceof Node\Expr\ArrowFunction) {
            if ($this->extractClosureCode($node)) {
                return NodeTraverser::STOP_TRAVERSAL;
            }
        }
        return null;
    }

    /**
     * @return bool True if code was extracted
     */
    private function extractClosureCode(Node $node): bool
    {
        // Closure started on a different line, under all normal coding
        // standards this is safe to detect we're at the right place in the
        // source file.
        if ($this->startLine !== $node->getStartLine()) {
            return false;
        }
        assert($this->endLine === $node->getEndLine());

        $printer = new Standard(['shortArraySyntax' => true]);
        $this->code = $printer->prettyPrint([$node]);
        return true;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
