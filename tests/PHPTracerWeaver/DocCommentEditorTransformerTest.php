<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\ScannerInterface;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\TokenBuffer;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPTracerWeaver\Transform\DocCommentEditorTransformer;
use PHPTracerWeaver\Transform\PassthruBufferEditor;
use PHPUnit\Framework\TestCase;

class TestOfDocCommentEditorTransformer extends TestCase
{
    /**
     * @param string               $source
     * @param PassthruBufferEditor $editor
     *
     * @return ScannerInterface
     */
    public function scan(string $source, PassthruBufferEditor $editor = null): ScannerInterface
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

    /**
     * @return void
     */
    public function testInputReturnsOutput(): void
    {
        $source = '<?php /** Lorem Ipsum */' . "\n" . 'function bar($x) {}' . "\n" . 'function zim($y) {}';
        $transformer = $this->scan($source);
        $this->assertSame($source, $transformer->getOutput());
    }

    /**
     * @return void
     */
    public function testInvokesEditorOnFunction(): void
    {
        $source = '<?php' . "\n" . 'function bar($x) {}';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertSame('function bar($x) ', $mockEditor->buffer->toText());
    }

    /**
     * @return void
     */
    public function testInvokesEditorOnFunctionModifiers(): void
    {
        $source = '<?php' . "\n" . 'class Foo { abstract function bar($x) {} }';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertSame('abstract function bar($x) ', $mockEditor->buffer->toText());
    }

    /**
     * @return void
     */
    public function testDoesntInvokeEditorOnClassModifiers(): void
    {
        $source = '<?php' . "\n" . 'abstract class Foo {}';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertNull($mockEditor->buffer);
    }

    /**
     * @return void
     */
    public function testInvokesEditorOnDocblock(): void
    {
        $source = '<?php' . "\n" . '/** Lorem Ipsum */' . "\n" . 'function bar($x) {}';
        $mockEditor = new MockPassthruBufferEditor();
        $transformer = $this->scan($source, $mockEditor);
        $this->assertInstanceOf(TokenBuffer::class, $mockEditor->buffer);
        $this->assertTrue($mockEditor->buffer->getFirstToken()->isA(T_DOC_COMMENT));
        $this->assertSame('/** Lorem Ipsum */' . "\n" . 'function bar($x) ', $mockEditor->buffer->toText());
    }
}
