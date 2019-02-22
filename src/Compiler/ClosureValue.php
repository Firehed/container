<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

use Closure;
use ReflectionFunction;

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
        $rf = new ReflectionFunction($this->closure);

        $sourceFile = $rf->getFileName();
        $startLine = $rf->getStartLine();
        $endLine = $rf->getEndLine();

        $tokens = token_get_all(file_get_contents($sourceFile));
        $relevantTokens = [];
        $atRelevant = false;
        // Simple first pass: grab the relevant chunk of the file
        foreach ($tokens as $token) {
            // Once we've hit the end, stop recording
            if (is_array($token) && $token[2] > $endLine) {
                break;
            }
            // Otherwise look for the start
            if (is_array($token) && $token[2] >= $startLine) {
                $atRelevant = true;
            }
            if ($atRelevant) {
                $relevantTokens[] = $token;
            }
        }

        // Now do a more specific pass
        $closureBody = [];
        $inDef = false;
        $braceDepth = 0;
        foreach ($relevantTokens as $token) {
            // Look for starting T_FUNCTION
            if (!$inDef) {
                if (is_array($token) && $token[0] === T_FUNCTION) {
                    $inDef = true;
                }
            }
            if ($inDef) {
                if ($token === '{') {
                    $braceDepth++;
                    // Skip the very first
                    // if ($braceDepth === 1) {
                        // continue;
                    // }
                }
                if ($braceDepth > 0) {
                    $closureBody[] = $token;
                }
                if ($token === '}') {
                    $braceDepth--;
                    if ($braceDepth === 0) {
                        break;
                    }
                }
            }
        }
        array_shift($closureBody); // Remove leading {
        array_pop($closureBody); // Remove trailing }
        // print_r($closureBody);
        $stringTokens = array_map(function ($token) {
            return is_array($token) ? $token[1] : $token;
        }, $closureBody);

        // print_r($stringTokens);

        $closureString = trim(implode('', $stringTokens));
        // echo $closureString;
        // var_dump($rf->getFileName());

        // var_dump($st, $en);
        // print_r($rf);exit;
        // $ex = var_export($def, true);
        return <<<PHP
protected function $functionName()
{
    // Sourced from $sourceFile [$startLine...$endLine]
    $closureString
}
PHP;
    }
}
