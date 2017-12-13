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
        $parametersScanner = $scanner->appendScanner(new FunctionParametersScanner());
        $functionBodyScanner = $scanner->appendScanner(new FunctionBodyScanner());
        $modifiersScanner = $scanner->appendScanner(new ModifiersScanner());
        $transformer = $scanner->appendScanner(
            new DocCommentEditorTransformer($functionBodyScanner, $modifiersScanner, $parametersScanner, $editor)
        );
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan($source);
        $tokenStream->iterate($scanner);

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
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertSame('function bar($x) ', $mockEditor->buffer->toText());
    }

    public function testInvokesEditorOnFunctionModifiers()
    {
        $source = '<?php' . "\n" . 'class Foo { abstract function bar($x) {} }';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertSame('abstract function bar($x) ', $mockEditor->buffer->toText());
    }

    public function testDoesntInvokeEditorOnClassModifiers()
    {
        $source = '<?php' . "\n" . 'abstract class Foo {}';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertNull($mockEditor->buffer);
    }

    public function testInvokesEditorOnDocblock()
    {
        $source = '<?php' . "\n" . '/** Lorem Ipsum */' . "\n" . 'function bar($x) {}';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertTrue($mockEditor->buffer->getFirstToken()->isA(T_DOC_COMMENT));
        $this->assertSame('/** Lorem Ipsum */' . "\n" . 'function bar($x) ', $mockEditor->buffer->toText());
    }
}
