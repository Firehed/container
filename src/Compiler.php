<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
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
    use BuilderTrait;

    /** @var class-string<TypedContainerInterface> */
    private string $className;

    /** @var array<string, Compiler\CodeGeneratorInterface> */
    private array $definitions = [];

    /** @var array<class-string, class-string[]> */
    private array $dependencies = [];

    /** @var ContainerExceptionInterface[] */
    private array $errors = [];

    private bool $exists;

    /** @var array<string, true> */
    private array $factories = [];

    private LoggerInterface $logger;

    private string $path;

    public function __construct(string $path = 'cc.php', ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        // @phpstan-ignore-next-line This class will be generated
        $this->className = 'CC_' . md5($path);

        // If the container has already been built, do nothing else.
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
        $this->exists = false;
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

        foreach ($this->processDefinitions($defs) as $key => $value) {
            $this->definitions[$key] = $value;
            if (!$value->isCacheable()) {
                $this->factories[$key] = true;
            }
        }
    }

    public function build(): TypedContainerInterface
    {
        $this->compile();
        require_once $this->path;
        return new $this->className(new EnvReader($_ENV));
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
        $tpl .= '    protected array $factories = ';
        $tpl .= var_export($this->factories, true);
        $tpl .= ";\n";
        $tpl .= '    protected array $mappings = ';
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
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromString('8.2'));
        $ast = $parser->parse($code);

        $printer = new Standard(['shortArraySyntax' => true]);
        assert($ast !== null);
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
