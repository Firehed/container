<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

interface CodeGeneratorInterface
{
    /**
     * Must return PHP code that evalutes to something like this:
     *
     *     return 'some_value';
     *
     * The outer function body will be provided by the calling compiler
     */
    public function generateCode(): string;
}
