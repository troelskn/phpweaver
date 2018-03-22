<?php namespace PHPWeaver\Transform;

use PHPWeaver\Scanner\TokenBuffer;

interface BufferEditorInterface
{
    /**
     * @param TokenBuffer $buffer
     *
     * @return void
     */
    public function editBuffer(TokenBuffer $buffer): void;
}
