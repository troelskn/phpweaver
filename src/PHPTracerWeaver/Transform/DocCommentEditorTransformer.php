<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\Token;
use PHPTracerWeaver\Scanner\TokenBuffer;

class DocCommentEditorTransformer implements TransformerInterface
{
    /** @var FunctionBodyScanner */
    protected $functionBodyScanner;
    /** @var ModifiersScanner */
    protected $modifiersScanner;
    /** @var FunctionParametersScanner */
    protected $parametersScanner;
    /** @var BufferEditorInterface */
    protected $editor;
    /** @var int */
    protected $state = 0;
    /** @var TokenBuffer */
    protected $buffer;

    /**
     * @param FunctionBodyScanner       $functionBodyScanner
     * @param ModifiersScanner          $modifiersScanner
     * @param FunctionParametersScanner $parametersScanner
     * @param BufferEditorInterface     $editor
     */
    public function __construct(
        FunctionBodyScanner $functionBodyScanner,
        ModifiersScanner $modifiersScanner,
        FunctionParametersScanner $parametersScanner,
        BufferEditorInterface $editor
    ) {
        $this->functionBodyScanner = $functionBodyScanner;
        $this->modifiersScanner = $modifiersScanner;
        $this->parametersScanner = $parametersScanner;
        $this->editor = $editor;
        $this->buffer = new TokenBuffer();
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->state = 1;
            $this->raiseBuffer();
        } elseif (0 === $this->state && ($this->modifiersScanner->isActive() || $token->isA(T_FUNCTION))) {
            $this->state = 1;
            $this->raiseBuffer();
        } elseif ($this->state > 0 && $this->functionBodyScanner->isActive()) {
            $this->editor->editBuffer($this->buffer);
            $this->state = 0;
            $this->flushBuffers();
        } elseif ($token->isA(T_INTERFACE)
            || $token->isA(T_CLASS)
            || ($token->isA(T_VARIABLE) && !$this->parametersScanner->isActive())
        ) {
            $this->state = 0;
            $this->flushBuffers();
        }
        $this->buffer->append($token);
    }

    /**
     * @return void
     */
    public function raiseBuffer(): void
    {
        $this->flushBuffers();
        $this->buffer = $this->buffer->raise();
    }

    /**
     * @return void
     */
    public function flushBuffers(): void
    {
        while ($this->buffer->hasSuper()) {
            $this->buffer = $this->buffer->flush();
        }
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        $this->flushBuffers();

        return $this->buffer->toText();
    }
}
