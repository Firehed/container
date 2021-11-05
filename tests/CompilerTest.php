<?php

declare(strict_types=1);

namespace Firehed\Container;

use Psr\Log\AbstractLogger;

/**
 * @covers Firehed\Container\Compiler
 * @covers Firehed\Container\CompiledContainer
 */
class CompilerTest extends \PHPUnit\Framework\TestCase
{
    use ContainerBuilderTestTrait;

    /** @var string */
    private $file;

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

    public function testMakingPathWritable(): void
    {
        $tmp = sys_get_temp_dir();
        $path = sprintf(
            '%s/%d/%d/%d.php',
            $tmp,
            random_int(0, PHP_INT_MAX),
            random_int(0, PHP_INT_MAX),
            random_int(0, PHP_INT_MAX),
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
