<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\Token;
use PHPTracerWeaver\Scanner\TokenBuffer;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPTracerWeaver\Transform\DocCommentEditorTransformer;
use PHPTracerWeaver\Transform\PassthruBufferEditor;
use PHPUnit\Framework\TestCase;

class DocCommentEditorTransformerTest extends TestCase
{
    /**
     * @param string                    $source
     * @param PassthruBufferEditor|null $editor
     *
     * @return DocCommentEditorTransformer
     */
    public function scan(string $source, PassthruBufferEditor $editor = null): DocCommentEditorTransformer
    {
        $editor = $editor ? $editor : new PassthruBufferEditor();
        $scanner = new ScannerMultiplexer();
        $parametersScanner = new FunctionParametersScanner();
        $scanner->appendScanner($parametersScanner);
        $functionBodyScanner = new FunctionBodyScanner();
        $scanner->appendScanner($functionBodyScanner);
        $modifiersScanner = new ModifiersScanner();
        $scanner->appendScanner($modifiersScanner);
        $transformer = new DocCommentEditorTransformer(
            $functionBodyScanner,
            $modifiersScanner,
            $parametersScanner,
            $editor
        );
        $scanner->appendScanner($transformer);
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
        $this->scan($source, $mockEditor);
        assert($mockEditor->buffer instanceof TokenBuffer);
        $this->assertSame('function bar($x) ', $mockEditor->buffer->toText());
    }

    /**
     * @return void
     */
    public function testInvokesEditorOnFunctionModifiers(): void
    {
        $source = '<?php' . "\n" . 'class Foo { abstract function bar($x) {} }';
        $mockEditor = new MockPassthruBufferEditor();
        $this->scan($source, $mockEditor);
        assert($mockEditor->buffer instanceof TokenBuffer);
        $this->assertSame('abstract function bar($x) ', $mockEditor->buffer->toText());
    }

    /**
     * @return void
     */
    public function testDoesntInvokeEditorOnClassModifiers(): void
    {
        $source = '<?php' . "\n" . 'abstract class Foo {}';
        $mockEditor = new MockPassthruBufferEditor();
        $this->scan($source, $mockEditor);
        $this->assertNull($mockEditor->buffer);
    }

    /**
     * @return void
     */
    public function testInvokesEditorOnDocblock(): void
    {
        $source = '<?php' . "\n" . '/** Lorem Ipsum */' . "\n" . 'function bar($x) {}';
        $mockEditor = new MockPassthruBufferEditor();
        $this->scan($source, $mockEditor);
        assert($mockEditor->buffer instanceof TokenBuffer);
        $token = $mockEditor->buffer->getFirstToken();
        assert($token instanceof Token);
        $this->assertTrue($token->isA(T_DOC_COMMENT));
        $this->assertSame('/** Lorem Ipsum */' . "\n" . 'function bar($x) ', $mockEditor->buffer->toText());
    }
}
