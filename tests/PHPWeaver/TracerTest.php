<?php namespace PHPWeaver\Test;

use PHPUnit\Framework\TestCase;
use PHPWeaver\Command\TraceCommand;
use PHPWeaver\Reflector\DummyClassCollator;
use PHPWeaver\Signature\Signatures;
use PHPWeaver\Xtrace\FunctionTracer;
use PHPWeaver\Xtrace\TraceReader;
use PHPWeaver\Xtrace\TraceSignatureLogger;
use SplFileObject;
use Symfony\Component\Console\Tester\CommandTester;

class TracerTest extends TestCase
{
    /** @var string */
    private $curdir = '';

    /**
     * @return string
     */
    public function bindir(): string
    {
        return __DIR__ . '/../..';
    }

    /**
     * @return string
     */
    public function sandbox(): string
    {
        return __DIR__ . '/../sandbox';
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->curdir = getcwd() ?: '';
        $dirSandbox = $this->sandbox();
        mkdir($dirSandbox);
        $sourceInclude = '<?php' . "\n" .
        'function callit($foo) {' . "\n" .
        '  return new Bar();' . "\n" .
        '}' . "\n" .
        'class Foo {' . "\n" .
        '  protected $val;' . "\n" .
        '  function __construct($val) {' . "\n" .
        '    $this->val = $val;' . "\n" .
        '  }' . "\n" .
        '}' . "\n" .
        'class Bar {' . "\n" .
        '}';
        file_put_contents($dirSandbox . '/include.php', $sourceInclude);
        $sourceMain = '<?php' . "\n" .
        'require_once "include.php";' . "\n" .
        'callit(new Foo(42));';
        file_put_contents($dirSandbox . '/main.php', $sourceMain);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        chdir($this->curdir);
        $dirSandbox = $this->sandbox();
        unlink($dirSandbox . '/include.php');
        unlink($dirSandbox . '/main.php');
        if (is_file($dirSandbox . '/dumpfile.xt')) {
            unlink($dirSandbox . '/dumpfile.xt');
        }
        rmdir($dirSandbox);
    }

    /**
     * @return void
     */
    public function testCanExecuteSandboxCode(): void
    {
        chdir($this->sandbox());
        exec('php ' . escapeshellarg($this->sandbox() . '/main.php'), $output, $returnVar);
        static::assertEmpty($output);
        static::assertSame(0, $returnVar);
    }

    /**
     * @return void
     */
    public function testCanExecuteSandboxCodeWithInstrumentation(): void
    {
        chdir($this->sandbox());
        $commandTester = new CommandTester(new TraceCommand());
        $commandTester->execute(['phpscript' => $this->sandbox() . '/main.php']);
        $output = $commandTester->getDisplay();
        static::assertStringEndsWith("TRACE COMPLETE\n", $output);
    }

    /**
     * @return void
     */
    public function testInstrumentationCreatesTracefile(): void
    {
        chdir($this->sandbox());
        $commandTester = new CommandTester(new TraceCommand());
        $commandTester->execute(['phpscript' => $this->sandbox() . '/main.php']);
        static::assertTrue(is_file($this->sandbox() . '/dumpfile.xt'));
    }

    /**
     * @return void
     */
    public function testCanParseTracefile(): void
    {
        chdir($this->sandbox());
        $commandTester = new CommandTester(new TraceCommand());
        $commandTester->execute(['phpscript' => $this->sandbox() . '/main.php']);
        $sigs = new Signatures();
        static::assertFalse($sigs->has('callit'));
        $collector = new TraceSignatureLogger($sigs);
        $trace = new TraceReader(new FunctionTracer($collector));
        foreach (new SplFileObject($this->sandbox() . '/dumpfile.xt') as $line) {
            static::assertIsString($line);
            $trace->processLine($line);
        }
        static::assertTrue($sigs->has('callit'));
    }

    /**
     * @return void
     */
    public function testCanParseClassArg(): void
    {
        chdir($this->sandbox());
        $commandTester = new CommandTester(new TraceCommand());
        $commandTester->execute(['phpscript' => $this->sandbox() . '/main.php']);
        $sigs = new Signatures();
        $collector = new TraceSignatureLogger($sigs);
        $trace = new TraceReader(new FunctionTracer($collector));
        foreach (new SplFileObject($this->sandbox() . '/dumpfile.xt') as $line) {
            static::assertIsString($line);
            $trace->processLine($line);
        }
        static::assertSame('Foo', $sigs->get('callit')->getArgumentById(0)->getType());
    }
}
