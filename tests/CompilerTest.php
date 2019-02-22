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
    use ContainerBuilderTestTrait {
        setUp as traitSetUp;
    }

    /** @var string */
    private $file;

    public function setUp(): void
    {
        $tmp = sys_get_temp_dir();
        $tmp = '.'; // FIXME remove
        $cc = sprintf('%s/%d.php', $tmp, random_int(0, PHP_INT_MAX));
        $this->file = $cc;
        $this->traitSetUp();
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
            private function interpolate($message, array $context = array())
            {
                // build a replacement array with braces around the context keys
                $replace = array();
                foreach ($context as $key => $val) {
                    // check that the value can be casted to string
                    if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                        $replace['{' . $key . '}'] = $val;
                    }
                }

                // interpolate replacement values into the message and return
                return strtr($message, $replace);
            }
            public function log($level, $message, $context = [])
            {
                // return;
                if ($level === 'debug') return;
                // if ($level === 'info') return;
                echo "[$level] " . $this->interpolate($message, $context) . "\n";
            }
        };
        return new Compiler($this->file, $logger);
    }
}
