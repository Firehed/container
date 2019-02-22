<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

interface CodeGeneratorInterface
{
    /**
     * Must return PHP code that evalutes to something like this:
     *     protected function $functionName() {
     *         return 'some_value';
     *     }
     */
    public function generateCode(string $functionName): string;
}
