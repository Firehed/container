<?php
declare(strict_types=1);

namespace Firehed\Container;

use Closure;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use UnexpectedValueException;

class Compiler implements BuilderInterface
{
    /** @var string */
    private $className;

    /** @var mixed[] */
    private $definitions = [];

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
        $this->className = 'CC_' . md5($path);

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
        if (!is_writable($info['dirname'])) {
            throw new UnexpectedValueException('Not writable');
        }
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
                $this->definitions[$key] = new Compiler\ClosureValue($value->getDefinition());
            } else {
                // $produceKey = 'newCopyOf'.ucfirst($key);
                // $this->definitions[$produceKey] = new Compiler\AutowiredValue($key);
                // $this->definitions[$key] = new Compiler\ProxyValue($produceKey);
                $this->definitions[$key] = new Compiler\AutowiredValue($key);
            }
            // $this->logger->error('FACTORY Unhandled value for {key}', ['key' => $key]);
        } elseif ($value instanceof AutowireInterface) {
            $wiredClass = $value->getWiredClass();
            if ($wiredClass === null) {
                $wiredClass = $key;
            }
            $this->definitions[$key] = new Compiler\AutowiredValue($wiredClass);
        } elseif ($value instanceof Closure) {
            // $this->logger->error('CLOSURE Unhandled value for {key}', ['key' => $key]);
            $this->definitions[$key] = new Compiler\ClosureValue($value);
        } elseif ($value instanceof EnvironmentVariableInterface) {
            $this->definitions[$key] = new Compiler\EnvironmentVariableValue($value);
        } elseif (interface_exists($key) && is_string($value)) {
            // Simple autowiring
            $this->logger->debug('Basic autowire {key} => {value}', [
                'key' => $key,
                'value' => $value,
            ]);
            // Never cache proxied values in case they point to a factory
            $this->factories[$key] = true;
            $this->definitions[$key] = new Compiler\ProxyValue($value);
        } else {
            $this->definitions[$key] = new Compiler\LiteralValue($value);
        }
    }

    public function build(): ContainerInterface
    {
        $defs = [];
        $mappings = [];
        foreach ($this->definitions as $key => $value) {
            // TODO: throw if a key collides
            $name = $this->makeNameForKey($key);
            $mappings[$key] = $name;

            $defs[] = $this->makeFunctionBody($name, $value);// $value->generateCode($name);
        }

        $tpl  = "<?php\n";
        $tpl .= "declare(strict_types=1);\n";
        $tpl .= '// this file is automatically @gener'."ated\n";
        $tpl .= "class {$this->className} extends \\Firehed\\Container\\CompiledContainer\n";
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

        $tpl = $this->prettyPrint($tpl);
        $this->logger->info($tpl);

        file_put_contents($this->path, $tpl);
        // var_dump($this->path);exit;
        require_once $this->path;
        return new $this->className();
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

    private function makeFunctionBody(string $functionName, Compiler\CodeGeneratorInterface $definition): string
    {
        $body = $definition->generateCode();
        return sprintf(
            'protected function %s() { %s }',
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
}
