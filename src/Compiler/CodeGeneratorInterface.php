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

    /**
     * This method must return an array of strings which correspond to $id
     * values (typically FQCNs)
     *
     * @return class-string[]
     */
    public function getDependencies(): array;
}
