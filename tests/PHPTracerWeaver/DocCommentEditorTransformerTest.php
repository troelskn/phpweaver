<?php

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\TokenBuffer;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPTracerWeaver\Transform\DocCommentEditorTransformer;
use PHPTracerWeaver\Transform\MockPassthruBufferEditor;
use PHPTracerWeaver\Transform\PassthruBufferEditor;
use PHPUnit\Framework\TestCase;

class TestOfDocCommentEditorTransformer extends TestCase
{
    public function scan($source, $editor = null)
    {
        $editor = $editor ? $editor : new PassthruBufferEditor();
        $scanner = new ScannerMultiplexer();
        $parameters_scanner = $scanner->appendScanner(new FunctionParametersScanner());
        $function_body_scanner = $scanner->appendScanner(new FunctionBodyScanner());
        $modifiers_scanner = $scanner->appendScanner(new ModifiersScanner());
        $transformer = $scanner->appendScanner(
            new DocCommentEditorTransformer($function_body_scanner, $modifiers_scanner, $parameters_scanner, $editor)
        );
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan($source);
        $token_stream->iterate($scanner);

        return $transformer;
    }

    public function testInputReturnsOutput()
    {
        $source = '<?php /** Lorem Ipsum */' . "\n" . 'function bar($x) {}' . "\n" . 'function zim($y) {}';
        $transformer = $this->scan($source);
        $this->assertSame($source, $transformer->getOutput());
    }

    public function testInvokesEditorOnFunction()
    {
        $source = '<?php' . "\n" . 'function bar($x) {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertInstanceOf(TokenBuffer::class, $mock_editor->buffer);
        $this->assertSame('function bar($x) ', $mock_editor->buffer->toText());
    }

    public function testInvokesEditorOnFunctionModifiers()
    {
        $source = '<?php' . "\n" . 'class Foo { abstract function bar($x) {} }';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertInstanceOf(TokenBuffer::class, $mock_editor->buffer);
        $this->assertSame('abstract function bar($x) ', $mock_editor->buffer->toText());
    }

    public function testDoesntInvokeEditorOnClassModifiers()
    {
        $source = '<?php' . "\n" . 'abstract class Foo {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertNull($mock_editor->buffer);
    }

    public function testInvokesEditorOnDocblock()
    {
        $source = '<?php' . "\n" . '/** Lorem Ipsum */' . "\n" . 'function bar($x) {}';
        $mock_editor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mock_editor);
        $this->assertInstanceOf(TokenBuffer::class, $mock_editor->buffer);
        $this->assertTrue($mock_editor->buffer->getFirstToken()->isA(T_DOC_COMMENT));
        $this->assertSame('/** Lorem Ipsum */' . "\n" . 'function bar($x) ', $mock_editor->buffer->toText());
    }
}
