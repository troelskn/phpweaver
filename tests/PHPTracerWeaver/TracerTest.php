<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Reflector\DummyClassCollator;
use PHPTracerWeaver\Signature\Signatures;
use PHPTracerWeaver\Xtrace\FunctionTracer;
use PHPTracerWeaver\Xtrace\TraceReader;
use PHPTracerWeaver\Xtrace\TraceSignatureLogger;
use PHPUnit\Framework\TestCase;
use SplFileObject;

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
    public function setUp()
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
        'callit(new Foo(42));' . "\n" .
        'echo "(completed)\n";';
        file_put_contents($dirSandbox . '/main.php', $sourceMain);
    }

    /**
     * @return void
     */
    public function tearDown()
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
        $output = shell_exec('php ' . escapeshellarg($this->sandbox() . '/main.php'));
        $this->assertSame("(completed)\n", $output);
    }

    /**
     * @return void
     */
    public function testCanExecuteSandboxCodeWithInstrumentation(): void
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/bin/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        $output = shell_exec($command);
        $this->assertRegExp('~\(completed\)~', $output);
        $this->assertRegExp('~TRACE COMPLETE\n$~', $output);
    }

    /**
     * @return void
     */
    public function testInstrumentationCreatesTracefile(): void
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/bin/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $this->assertTrue(is_file($this->sandbox() . '/dumpfile.xt'));
    }

    /**
     * @return void
     */
    public function testCanParseTracefile(): void
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/bin/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $sigs = new Signatures(new DummyClassCollator());
        $this->assertFalse($sigs->has('callit'));
        $trace = new TraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new TraceSignatureLogger($sigs);
        $trace->process(new FunctionTracer($collector));
        $this->assertTrue($sigs->has('callit'));
    }

    /**
     * @return void
     */
    public function testCanParseClassArg(): void
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/bin/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $sigs = new Signatures(new DummyClassCollator());
        $trace = new TraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new TraceSignatureLogger($sigs);
        $trace->process(new FunctionTracer($collector));
        $this->assertSame('Foo', $sigs->get('callit')->getArgumentById(0)->getType());
    }
}
