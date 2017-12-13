<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\TokenBuffer;

interface BufferEditorInterface
{
    /**
     * @param TokenBuffer $buffer
     *
     * @return void
     */
    public function editBuffer(TokenBuffer $buffer): void;
}
