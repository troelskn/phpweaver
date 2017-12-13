<?php

use PHPTracerWeaver\Reflector\StaticReflector;
use PHPTracerWeaver\Signature\Signatures;
use PHPTracerWeaver\Xtrace\FunctionTracer;
use PHPTracerWeaver\Xtrace\TraceReader;
use PHPTracerWeaver\Xtrace\TraceSignatureLogger;
use PHPUnit\Framework\TestCase;

class TestOfCollation extends TestCase
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
        $sourceMain = '<?php' . "\n" .
        'class Foo {' . "\n" .
        '}' . "\n" .
        'class Bar extends Foo {' . "\n" .
        '}' . "\n" .
        'class Cuux extends Foo {' . "\n" .
        '}' . "\n" .
        'function do_stuff($x) {}' . "\n" .
        'do_stuff(new Bar());' . "\n" .
        'do_stuff(new Cuux());';
        file_put_contents($dirSandbox . '/main.php', $sourceMain);
    }

    public function tearDown()
    {
        chdir($this->curdir);
        $dirSandbox = $this->sandbox();
        unlink($dirSandbox . '/main.php');
        if (is_file($dirSandbox . '/dumpfile.xt')) {
            unlink($dirSandbox . '/dumpfile.xt');
        }
        rmdir($dirSandbox);
    }

    public function testCanCollateClasses()
    {
        chdir($this->sandbox());
        $command = escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php');
        shell_exec($command);
        $reflector = new StaticReflector();
        $sigs = new Signatures($reflector);
        $trace = new TraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new TraceSignatureLogger($sigs, $reflector);
        $trace->process(new FunctionTracer($collector));
        $this->assertSame('Bar|Cuux', $sigs->get('do_stuff')->getArgumentById(0)->getType());
    }
}
