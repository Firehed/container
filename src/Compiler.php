<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use UnexpectedValueException;
use UnitEnum;

use function array_key_exists;
use function assert;
use function class_exists;
use function file_exists;
use function is_array;
use function is_int;
use function is_scalar;
use function is_string;
use function is_writable;
use function pathinfo;
use function sprintf;
use function realpath;

class Compiler implements BuilderInterface
{
    /** @var class-string<TypedContainerInterface> */
    private $className;

    /** @var Compiler\CodeGeneratorInterface[] */
    private $definitions = [];

    /** @var array<class-string, class-string[]> */
    private $dependencies = [];

    /** @var ContainerExceptionInterface[] */
    private $errors = [];

    /** @var bool */
    private $exists;

    /** @var array<string, true> */
    private $factories = [];

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $path;

    public function __construct(string $path = 'cc.php', ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        // @phpstan-ignore-next-line This class will be generated
        $this->className = 'CC_' . md5($path);

        // If the conatiner has already been built, do nothing else.
        if (file_exists($path)) {
            $this->exists = true;
            $this->path = $path;
            return;
        }

        $info = pathinfo($path);
        assert(isset($info['extension']));
        if ($info['extension'] !== 'php') {
            throw new UnexpectedValueException('Must be a php file');
        }
        $this->tryToMakePathWritable($path);
        $this->path = $path;
    }

    public function addFile(string $file): void
    {
        $this->logger->debug('Adding file {file}', ['file' => $file]);
        if ($this->exists) {
            return;
        }
        $defs = require $file;
        if (!is_array($defs)) {
            throw new UnexpectedValueException(sprintf(
                'File %s did not return an array',
                $file
            ));
        }
        foreach ($defs as $key => $value) {
            if (is_int($key)) {
                $this->logger->debug('Treating bare value {value} as autowired', [
                    'value' => $value,
                ]);
                $key = $value;
                $value = autowire();
            }
            $this->add($key, $value);
        }
    }

    /**
     * @param mixed $value
     */
    private function add(string $key, $value): void
    {
        $this->logger->debug('Adding definition for "{key}"', ['key' => $key]);
        if ($value instanceof FactoryInterface) {
            $this->factories[$key] = true;
            if ($value->hasDefinition()) {
                // Something::class => factory(fn ($container) => new Something(...))
                $this->definitions[$key] = new Compiler\ClosureValue($value->getDefinition());
            } else {
                if (class_exists($key)) {
                    // Something::class => factory()
                    $this->definitions[$key] = new Compiler\AutowiredValue($key);
                } else {
                    $this->errors[] = new Exceptions\AmbiguousMapping($key);
                }
            }
        } elseif ($value instanceof AutowireInterface) {
            // someName => autowire(...)
            // someName,
            $wiredClass = $value->getWiredClass();
            // autowire called without parameters: assume key is destination
            if ($wiredClass === null) {
                $wiredClass = $key;
            }
            if (class_exists($wiredClass)) {
                $this->definitions[$key] = new Compiler\AutowiredValue($wiredClass);
            } else {
                $this->errors[] = new Exceptions\AmbiguousMapping($key);
            }
        } elseif ($value instanceof Closure) {
            // someName => fn ($container) => new Something(...)
            $this->definitions[$key] = new Compiler\ClosureValue($value);
        } elseif ($value instanceof EnvironmentVariableInterface) {
            // someName => env('SOME_NAME')
            $this->definitions[$key] = new Compiler\EnvironmentVariableValue($value);
        } elseif (interface_exists($key)) {
            assert(is_string($value), 'Values without keys must be strings that correspond to autowirable classes');
            if (class_exists($value)) {
                // Simple autowiring
                // SomeInterface::class => Something::class
                $this->logger->debug('Basic autowire {key} => {value}', [
                    'key' => $key,
                    'value' => $value,
                ]);
                // Never cache proxied values in case they point to a factory
                $this->factories[$key] = true;
                $this->definitions[$key] = new Compiler\ProxyValue($key, $value);
            } else {
                $this->errors[] = new Exceptions\InvalidClassMapping($key, $value);
                    // SomeInterface::class => nonClassString
            }
        } else {
            assert(
                is_scalar($value)
                || is_array($value)
                || $value === null
                || $value instanceof UnitEnum,
                'Literal values must be scalars or arrays of scalars'
            );
            $this->definitions[$key] = new Compiler\LiteralValue($value);
        }
    }

    public function build(): TypedContainerInterface
    {
        $this->compile();
        require_once $this->path;
        return new $this->className();
    }

    private function compile(): void
    {
        if ($this->errors !== []) {
            // Ideally all would be thrown, but then there's all sorts of messy
            // chaining to handle.
            throw $this->errors[0];
        }
        if ($this->exists) {
            return;
        }
        $defs = [];
        $mappings = [];
        foreach ($this->definitions as $key => $value) {
            $name = $this->makeNameForKey($key);
            $mappings[$key] = $name;
            $defs[] = $this->makeFunctionBody($key, $name, $value);
        }

        // makeFunctionBody fills in dependencies
        foreach ($this->dependencies as $name => $requirementSources) {
            if (!array_key_exists($name, $mappings)) {
                throw Exceptions\NotFound::autowireMissing($name, $requirementSources[0]);
            }
        }

        $tpl  = "<?php\n";
        $tpl .= "declare(strict_types=1);\n";
        $tpl .= '// this file is automatically @gener'."ated\n";
        $tpl .= "class {$this->className}\n";
        $tpl .= "extends \\Firehed\\Container\\CompiledContainer\n";
        $tpl .= "{\n";
        $tpl .= '    protected $factories = ';
        $tpl .= var_export($this->factories, true);
        $tpl .= ";\n";
        $tpl .= '    protected $mappings = ';
        $tpl .= var_export($mappings, true);
        $tpl .= ";\n";

        $tpl .= implode("\n\n", $defs);
        $tpl .= "\n";
        $tpl .= "}\n";

        $code = $this->prettyPrint($tpl);
        $this->logger->info($code);

        file_put_contents($this->path, $code);
    }

    private function prettyPrint(string $code): string
    {
        $parser = (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        $printer = new Standard(['shortArraySyntax' => true]);
        assert($ast != null);
        return $printer->prettyPrintFile($ast);
    }

    private function makeFunctionBody(
        string $originalName,
        string $functionName,
        Compiler\CodeGeneratorInterface $definition
    ): string {
        $body = $definition->generateCode();
        foreach ($definition->getDependencies() as $dependency) {
            assert(class_exists($originalName) || interface_exists($originalName) || enum_exists($originalName));
            $this->dependencies[$dependency][] = $originalName;
        }
        return sprintf(
            "// %s\nprotected function %s() { %s }",
            $originalName,
            $functionName,
            $body
        );
    }

    private function makeNameForKey(string $key): string
    {
        $out = sprintf('get%s', md5($key));
        $this->logger->debug('Name generated: {key} => {out}', [
            'key' => $key,
            'out' => $out,
        ]);
        return $out;
    }

    /**
     * Asserts that it's possible to write to the intended destination. This
     * will try to create intermediate directories if necessary.
     *
     * @throws UnexpectedValueException if the path cannot be made writable
     */
    private function tryToMakePathWritable(string $destFile): void
    {
        $pathInfo = pathinfo($destFile);
        assert(array_key_exists('dirname', $pathInfo));

        if (is_writable($pathInfo['dirname'])) {
            // Directory exists and is writable, should be ok.
            return;
        }

        $this->logger->debug('{file} is not writable, making directories', [
            'file' => $destFile,
        ]);
        $result = mkdir($pathInfo['dirname'], 0700, true);
        if ($result === false) {
            throw new UnexpectedValueException('Not writable');
        }
        // Successfully made writable directory
    }
}
