<?php

use PHPTracerWeaver\Reflector\DummyClassCollator;
use PHPTracerWeaver\Signature\Signatures;
use PHPTracerWeaver\Xtrace\FunctionTracer;
use PHPTracerWeaver\Xtrace\TraceReader;
use PHPTracerWeaver\Xtrace\TraceSignatureLogger;
use PHPUnit\Framework\TestCase;

class TestOfTracer extends TestCase
{
    private $curdir;

    public function bindir()
    {
        return __DIR__ . '/../..';
    }

    public function sandbox()
    {
        return __DIR__ . '/../sandbox';
    }

    public function setUp()
    {
        $this->curdir = getcwd();
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

    public function testCanExecuteSandboxCode()
    {
        chdir($this->sandbox());
        $output = shell_exec('php ' . escapeshellarg($this->sandbox() . '/main.php'));
        $this->assertSame("(completed)\n", $output);
    }

    public function testCanExecuteSandboxCodeWithInstrumentation()
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        $output = shell_exec($command);
        //$this->dump("\n----\n" . $output . "\n----\n");
        $this->assertRegExp('~\(completed\)~', $output);
        $this->assertRegExp('~TRACE COMPLETE\n$~', $output);
    }

    public function testInstrumentationCreatesTracefile()
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        $output = shell_exec($command);
        $this->assertTrue(is_file($this->sandbox() . '/dumpfile.xt'));
    }

    public function testCanParseTracefile()
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $sigs = new Signatures(new DummyClassCollator());
        $this->assertFalse($sigs->has('callit'));
        $trace = new TraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new TraceSignatureLogger($sigs);
        $trace->process(new FunctionTracer($collector));
        $this->assertTrue($sigs->has('callit'));
    }

    public function testCanParseClassArg()
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $sigs = new Signatures(new DummyClassCollator());
        $trace = new TraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new TraceSignatureLogger($sigs);
        $trace->process(new FunctionTracer($collector));
        $this->assertSame('Foo', $sigs->get('callit')->getArgumentById(0)->getType());
    }
}
