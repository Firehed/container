<?php

declare(strict_types=1);

namespace Firehed\Container;

use function sprintf;
use function var_export;

/**
 * Reads from the file named by `{$name}_FILE` if that is set, otherwise from
 * `$name` itself.
 *
 * @internal
 */
class FileOrEnvSource implements ValueSourceInterface
{
    public function __construct(private string $name)
    {
    }

    public function generateReadExpression(): string
    {
        return sprintf('(new \\%s(%s))->read($this->envReader)', self::class, var_export($this->name, true));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function read(EnvReader $envReader): ?string
    {
        $fileName = $this->name . '_FILE';
        $path = $envReader->read($fileName);
        $value = $envReader->read($this->name);
        if ($path === null) {
            return $value;
        }
        if ($value !== null) {
            throw new Exceptions\ConflictingEnvironmentVariables($this->name, $fileName);
        }
        return (new FileSource($path))->read($envReader);
    }
}
