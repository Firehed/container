<?php
declare(strict_types=1);

namespace Firehed\Container\Compiler;

/**
 * This maps an interface to a class-string implementation
 */
class ProxyValue implements CodeGeneratorInterface
{
    /** @var class-string */
    private string $class;

    /**
     * @param class-string $interfaceName
     * @param class-string $classToAutowire
     */
    public function __construct(string $interfaceName, string $classToAutowire)
    {
        assert(class_exists($classToAutowire));
        // TODO: Warn if class doesn't implement interface?
        $this->class = $classToAutowire;
    }

    public function generateCode(): string
    {
        return sprintf(
            'return $this->get(%s);',
            var_export($this->class, true)
        );
    }

    public function getDependencies(): array
    {
        return [
            $this->class,
        ];
    }
}
