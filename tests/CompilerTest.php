<?php
declare(strict_types=1);

namespace Firehed\Container;

use Psr\Log\AbstractLogger;

/**
 * @coversDefaultClass Firehed\Container\Compiler
 * @covers ::<protected>
 * @covers ::<private>
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
        $logger = new \Firehed\SimpleLogger\Stderr();
        $logger->setLevel('error');
        return new Compiler($this->file, $logger);
    }
}
