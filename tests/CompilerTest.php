<?php

declare(strict_types=1);

namespace Firehed\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[CoversClass(AliasDefinition::class)]
#[CoversClass(AutowiredClass::class)]
#[CoversClass(ClosureDefinition::class)]
#[CoversClass(CompiledContainer::class)]
#[CoversClass(Compiler::class)]
#[CoversClass(Compiler\AutowiredValue::class)]
#[CoversClass(Compiler\ClosureVisitor::class)]
#[CoversClass(EnvironmentVariable::class)]
#[CoversClass(Exceptions\IncorrectlyTypedValue::class)]
#[CoversClass(Factory::class)]
#[CoversClass(ScalarDefinition::class)]
class CompilerTest extends TestCase
{
    use ContainerBuilderTestTrait;

    private string $file;

    public function setUp(): void
    {
        $tmp = sys_get_temp_dir();
        $tmp = '.'; // FIXME remove
        $cc = sprintf('%s/%d.php', $tmp, random_int(0, PHP_INT_MAX));
        $this->file = $cc;
    }
    
    public function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    protected function getBuilder(): BuilderInterface
    {
        $logger = new class extends AbstractLogger
        {
            /**
             * @inheritdoc
             * @param string|\Stringable $message
             * @param mixed[] $context
             */
            public function log($level, $message, array $context = []): void
            {
                if ($level === 'debug' || $level === 'info') {
                    return;
                }
                $ctx = json_encode($context);
                assert(is_string($level));
                fwrite(STDERR, "[$level] $message ($ctx)\n");
            }
        };
        return new Compiler($this->file, $logger);
    }

    public function testCompiledFileLeavesNoTemporaryFilesBehind(): void
    {
        $dir = sprintf('%s/%d', sys_get_temp_dir(), random_int(0, PHP_INT_MAX));
        $path = $dir . '/cc.php';
        $compiler = new Compiler($path);
        $compiler->addFile(__DIR__ . '/ValidDefinitions/Literals.php');
        try {
            $compiler->build();
            self::assertFileExists($path, 'File was not written');
            self::assertSame([$path], glob($dir . '/*'), 'Temporary file was left behind');
        } finally {
            $leftovers = glob($dir . '/*');
            if ($leftovers !== false) {
                foreach ($leftovers as $file) {
                    unlink($file);
                }
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function testCompiledFileIsReadableByOtherUsers(): void
    {
        $path = sprintf('%s/%d.php', sys_get_temp_dir(), random_int(0, PHP_INT_MAX));
        $compiler = new Compiler($path);
        $compiler->addFile(__DIR__ . '/ValidDefinitions/Literals.php');
        try {
            $compiler->build();
            $expected = 0666 & ~umask();
            self::assertSame($expected, fileperms($path) & 0777, 'File permissions do not follow umask');
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testMakingPathWritable(): void
    {
        $tmp = sys_get_temp_dir();
        $path = sprintf(
            '%s/%d/%d/%d.php',
            $tmp,
            random_int(0, PHP_INT_MAX),
            random_int(0, PHP_INT_MAX),
            random_int(0, PHP_INT_MAX)
        );
        assert(!is_writable($path));
        $compiler = new Compiler($path);
        $compiler->addFile(__DIR__ . '/ValidDefinitions/Literals.php');
        try {
            $container = $compiler->build();
            self::assertFileExists($path, 'File was not written');
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
