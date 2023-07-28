<?php namespace PHPWeaver\Transform;

use PHPWeaver\Scanner\FunctionBodyScanner;
use PHPWeaver\Scanner\FunctionParametersScanner;
use PHPWeaver\Scanner\ModifiersScanner;
use PHPWeaver\Scanner\Token;
use PHPWeaver\Scanner\TokenBuffer;

class DocCommentEditorTransformer implements TransformerInterface
{
    protected FunctionBodyScanner $functionBodyScanner;
    protected ModifiersScanner $modifiersScanner;
    protected FunctionParametersScanner $parametersScanner;
    protected BufferEditorInterface $editor;
    protected int $state = 0;
    protected TokenBuffer $buffer;

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

    public function raiseBuffer(): void
    {
        $this->flushBuffers();
        $this->buffer = $this->buffer->raise();
    }

    public function flushBuffers(): void
    {
        while ($this->buffer->hasSuper()) {
            $this->buffer = $this->buffer->flush();
        }
    }

    public function getOutput(): string
    {
        $this->flushBuffers();

        return $this->buffer->toText();
    }
}
