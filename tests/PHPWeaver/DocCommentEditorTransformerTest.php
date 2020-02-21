<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\FunctionBodyScanner;
use Mockery;
use PHPWeaver\Scanner\FunctionParametersScanner;
use PHPWeaver\Scanner\ModifiersScanner;
use PHPWeaver\Scanner\ScannerMultiplexer;
use PHPWeaver\Scanner\Token;
use PHPWeaver\Scanner\TokenBuffer;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPWeaver\Transform\DocCommentEditorTransformer;
use PHPWeaver\Transform\BufferEditorInterface;
use PHPUnit\Framework\TestCase;

class DocCommentEditorTransformerTest extends TestCase
{
    /**
     * @param string                     $source
     * @param BufferEditorInterface|null $editor
     *
     * @return DocCommentEditorTransformer
     */
    public function scan(string $source, BufferEditorInterface $editor = null): DocCommentEditorTransformer
    {
        /** @var BufferEditorInterface */
        $editor = $editor ? $editor : Mockery::spy(BufferEditorInterface::class);
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
        static::assertSame($source, $transformer->getOutput());
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
        static::assertSame('function bar($x) ', $mockEditor->buffer->toText());
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
        static::assertSame('abstract function bar($x) ', $mockEditor->buffer->toText());
    }

    /**
     * @return void
     */
    public function testDoesntInvokeEditorOnClassModifiers(): void
    {
        $source = '<?php' . "\n" . 'abstract class Foo {}';
        $mockEditor = new MockPassthruBufferEditor();
        $this->scan($source, $mockEditor);
        static::assertNull($mockEditor->buffer);
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
        static::assertTrue($token->isA(T_DOC_COMMENT));
        static::assertSame('/** Lorem Ipsum */' . "\n" . 'function bar($x) ', $mockEditor->buffer->toText());
    }
}
