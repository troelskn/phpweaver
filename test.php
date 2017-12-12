<?php
error_reporting(E_ALL | E_STRICT);

/**
 * todo:
 *   xtrace -> resources ..
 *   use static typehints for parameter types
 *   use docblock comments for parameter types
 *   merge with existing docblock comments.
 */

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/mock_objects.php';
if (__FILE__ == realpath($_SERVER['PHP_SELF'])) {
    require_once 'simpletest/autorun.php';
}
require_once 'signature.inc.php';
require_once 'xtrace.inc.php';
require_once 'scanner.inc.php';
require_once 'transform.inc.php';
require_once 'reflector.inc.php';

class TestOfTokenizer extends UnitTestCase
{
    public function test_tokenize_php_without_errors()
    {
        $tokenizer = new TokenStreamParser();
        $tokenizer->scan('<' . '?php function foo($x) {} ?' . '>');
    }
}

class TestOfClassScanner extends UnitTestCase
{
    public function test_can_track_current_class()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Foo { function bar($x) {}');
        $token_stream->iterate($scanner);
        $this->assertEqual($scanner->getCurrentClass(), 'Foo');
    }

    public function test_forgets_class_when_scope_ends()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Foo { function bar($x) {} }');
        $token_stream->iterate($scanner);
        $this->assertEqual($scanner->getCurrentClass(), null);
    }

    public function test_forgets_class_when_scope_ends_within_nested_scopes()
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php while (true) { class Foo { function bar($x) {} } }');
        $token_stream->iterate($scanner);
        $this->assertEqual($scanner->getCurrentClass(), null);
    }
}

class test_callbackListener
{
    protected $results = [];

    public function results()
    {
        return $this->results;
    }

    public function one()
    {
        if (1 !== count($this->results)) {
            throw new Exception('Expected exactly one result');
        }

        return $this->results[0];
    }

    public function call()
    {
        $this->results[] = func_get_args();
    }
}

class TestOfClassExtendsScanner extends UnitTestCase
{
    public function test_can_track_single_extends()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new test_callbackListener();
        $inheritance_scanner->notifyOnExtends([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Foo extends Bar {}');
        $token_stream->iterate($scanner);
        $this->assertEqual($listener->one(), ['Foo', 'Bar']);
    }

    public function test_can_track_single_implements()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new test_callbackListener();
        $inheritance_scanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Foo implements Bar {}');
        $token_stream->iterate($scanner);
        $this->assertEqual($listener->one(), ['Foo', 'Bar']);
    }

    public function test_can_track_multiple_implements()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new test_callbackListener();
        $inheritance_scanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Foo implements Bar, Doink {}');
        $token_stream->iterate($scanner);
        $this->assertEqual($listener->results(), [['Foo', 'Bar'], ['Foo', 'Doink']]);
    }
}

class TestOfStaticReflector extends UnitTestCase
{
    public function test_can_scan_sources()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<' . '?php class Foo implements Bar, Doink {}');
        $reflector->scanString('<' . '?php class Zip implements Bar {}');
        $this->assertEqual($reflector->ancestors('Foo'), ['Bar', 'Doink']);
        $this->assertEqual($reflector->ancestors('Zip'), ['Bar']);
    }

    public function test_can_collate_same()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<' . '?php class Foo extends Bar {}');
        $reflector->scanString('<' . '?php class Zip extends Bar {}');
        $this->assertEqual($reflector->collate('Foo', 'Foo'), 'Foo');
    }

    public function test_can_collate_direct_inheritance()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<' . '?php class Foo extends Bar {}');
        $reflector->scanString('<' . '?php class Zip extends Bar {}');
        $this->assertEqual($reflector->collate('Foo', 'Zip'), 'Bar');
    }

    public function test_can_collate_child_to_parent()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<' . '?php class Foo {}');
        $reflector->scanString('<' . '?php class Bar extends Foo {}');
        $this->assertEqual($reflector->collate('Foo', 'Bar'), 'Foo');
    }

    public function test_can_collate_parent_to_child()
    {
        $reflector = new StaticReflector();
        $reflector->scanString('<' . '?php class Foo {}');
        $reflector->scanString('<' . '?php class Bar extends Foo {}');
        $this->assertEqual($reflector->collate('Bar', 'Foo'), 'Foo');
    }
}

class TestOfFunctionParametersScanner extends UnitTestCase
{
    public function test_can_track_current_signature_for_function()
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php function bar($x) {}');
        $token_stream->iterate($scanner);
        $this->assertEqual($scanner->getCurrentSignatureAsString(), '($x)');
    }

    public function test_scanner_is_active_after_first_opening_paren()
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php function bar(');
        $this->assertFalse($scanner->isActive());
        $token_stream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }
}

class TestOfModifiersScanner extends UnitTestCase
{
    public function test_can_track_modifiers()
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php final static protected');
        $this->assertFalse($scanner->isActive());
        $token_stream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }

    public function test_ends_on_function()
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php final static protected function foo() {} ');
        $token_stream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}

class TestOfFunctionBodyScanner extends UnitTestCase
{
    public function test_can_track_function_body()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php function bar() {');
        $this->assertFalse($scanner->isActive());
        $token_stream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }

    public function test_can_track_end_of_function_body()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php function bar() { if (true) {} }');
        $token_stream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    public function test_can_track_function_name()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php function bar() { print 42;');
        $token_stream->iterate($scanner);
        $this->assertEqual($scanner->getName(), 'bar');
    }

    public function test_can_track_end_of_scoped_function_body()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<' . '?php class Fizz { function buzz() { if (true) {} }');
        $token_stream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}

class MockPassthruBufferEditor extends PassthruBufferEditor
{
    public $buffer;

    public function editBuffer(TokenBuffer $buffer)
    {
        $this->buffer = clone $buffer;
    }
}

class TestOfDocCommentEditorTransformer extends UnitTestCase
{
    public function scan($source, $editor = null)
    {
        $editor = $editor ? $editor : new PassthruBufferEditor();
        $scanner = new ScannerMultiplexer();
        $parameters_scanner = $scanner->appendScanner(new FunctionParametersScanner());
        $function_body_scanner = $scanner->appendScanner(new FunctionBodyScanner());
        $modifiers_scanner = $scanner->appendScanner(new ModifiersScanner());
        $transformer = $scanner->appendScanner(new DocCommentEditorTransformer($function_body_scanner, $modifiers_scanner, $parameters_scanner, $editor));
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan($source);
        $token_stream->iterate($scanner);

        return $transformer;
    }

    public function test_input_returns_output()
    {
        $source = '<' . '?php /** Lorem Ipsum */' . "\n" . 'function bar($x) {}' . "\n" . 'function zim($y) {}';
        $transformer = $this->scan($source);
        $this->assertEqual($transformer->getOutput(), $source);
    }

    public function test_invokes_editor_on_function()
    {
        $source = '<' . '?php' . "\n" . 'function bar($x) {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertIsA($mock_editor->buffer, 'TokenBuffer');
        $this->assertEqual($mock_editor->buffer->toText(), 'function bar($x) ');
    }

    public function test_invokes_editor_on_function_modifiers()
    {
        $source = '<' . '?php' . "\n" . 'abstract function bar($x) {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertIsA($mock_editor->buffer, 'TokenBuffer');
        $this->assertEqual($mock_editor->buffer->toText(), 'abstract function bar($x) ');
    }

    public function test_doesnt_invoke_editor_on_class_modifiers()
    {
        $source = '<' . '?php' . "\n" . 'abstract class Foo {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertNull($mock_editor->buffer);
    }

    public function test_invokes_editor_on_docblock()
    {
        $source = '<' . '?php' . "\n" . '/** Lorem Ipsum */' . "\n" . 'function bar($x) {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertIsA($mock_editor->buffer, 'TokenBuffer');
        $this->assertTrue($mock_editor->buffer->getFirstToken()->isA(T_DOC_COMMENT));
        $this->assertEqual($mock_editor->buffer->toText(), '/** Lorem Ipsum */' . "\n" . 'function bar($x) ');
    }
}

class TestOfTracer extends UnitTestCase
{
    public function bindir()
    {
        return dirname(__FILE__);
    }

    public function sandbox()
    {
        return dirname(__FILE__) . '/sandbox';
    }

    public function setUp()
    {
        $this->curdir = getcwd();
        $dir_sandbox = $this->sandbox();
        mkdir($dir_sandbox);
        $source_include = '<' . '?php' . "\n" .
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
        file_put_contents($dir_sandbox . '/include.php', $source_include);
        $source_main = '<' . '?php' . "\n" .
        'require_once "include.php";' . "\n" .
        'callit(new Foo(42));' . "\n" .
        'echo "(completed)\n";';
        file_put_contents($dir_sandbox . '/main.php', $source_main);
    }

    public function tearDown()
    {
        chdir($this->curdir);
        $dir_sandbox = $this->sandbox();
        unlink($dir_sandbox . '/include.php');
        unlink($dir_sandbox . '/main.php');
        if (is_file($dir_sandbox . '/dumpfile.xt')) {
            unlink($dir_sandbox . '/dumpfile.xt');
        }
        rmdir($dir_sandbox);
    }

    public function test_can_execute_sandbox_code()
    {
        chdir($this->sandbox());
        $output = shell_exec('php ' . escapeshellarg($this->sandbox() . '/main.php'));
        $this->assertEqual("(completed)\n", $output);
    }

    public function test_can_execute_sandbox_code_with_instrumentation()
    {
        chdir($this->sandbox());
        $output = shell_exec(escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php'));
        //$this->dump("\n----\n" . $output . "\n----\n");
        $this->assertPattern('~\(completed\)~', $output);
        $this->assertPattern('~TRACE COMPLETE\n$~', $output);
    }

    public function test_instrumentation_creates_tracefile()
    {
        chdir($this->sandbox());
        $output = shell_exec(escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php'));
        $this->assertTrue(is_file($this->sandbox() . '/dumpfile.xt'));
    }

    public function test_can_parse_tracefile()
    {
        chdir($this->sandbox());
        shell_exec(escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php'));
        $sigs = new Signatures(new DummyClassCollator());
        $this->assertFalse($sigs->has('callit'));
        $trace = new XtraceTraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new XtraceTraceSignatureLogger($sigs);
        $trace->process(new XtraceFunctionTracer($collector));
        $this->assertTrue($sigs->has('callit'));
    }

    public function test_can_parse_class_arg()
    {
        chdir($this->sandbox());
        shell_exec(escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php'));
        $sigs = new Signatures(new DummyClassCollator());
        $trace = new XtraceTraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new XtraceTraceSignatureLogger($sigs);
        $trace->process(new XtraceFunctionTracer($collector));
        $this->assertEqual('Foo', $sigs->get('callit')->getArgumentById(0)->getType());
    }
}

class TestOfCollation extends UnitTestCase
{
    public function bindir()
    {
        return dirname(__FILE__);
    }

    public function sandbox()
    {
        return dirname(__FILE__) . '/sandbox';
    }

    public function setUp()
    {
        $this->curdir = getcwd();
        $dir_sandbox = $this->sandbox();
        mkdir($dir_sandbox);
        $source_main = '<' . '?php' . "\n" .
        'class Foo {' . "\n" .
        '}' . "\n" .
        'class Bar extends Foo {' . "\n" .
        '}' . "\n" .
        'class Cuux extends Foo {' . "\n" .
        '}' . "\n" .
        'function do_stuff($x) {}' . "\n" .
        'do_stuff(new Bar());' . "\n" .
        'do_stuff(new Cuux());';
        file_put_contents($dir_sandbox . '/main.php', $source_main);
    }

    public function tearDown()
    {
        chdir($this->curdir);
        $dir_sandbox = $this->sandbox();
        unlink($dir_sandbox . '/main.php');
        if (is_file($dir_sandbox . '/dumpfile.xt')) {
            unlink($dir_sandbox . '/dumpfile.xt');
        }
        rmdir($dir_sandbox);
    }

    public function test_can_collate_classes()
    {
        chdir($this->sandbox());
        shell_exec(escapeshellcmd($this->bindir() . '/trace.sh') . ' ' . escapeshellarg($this->sandbox() . '/main.php'));
        $reflector = new StaticReflector();
        $sigs = new Signatures($reflector);
        $trace = new XtraceTraceReader(new SplFileObject($this->sandbox() . '/dumpfile.xt'));
        $collector = new XtraceTraceSignatureLogger($sigs, $reflector);
        $trace->process(new XtraceFunctionTracer($collector));
        $this->assertEqual('Foo', $sigs->get('do_stuff')->getArgumentById(0)->getType());
    }
}
