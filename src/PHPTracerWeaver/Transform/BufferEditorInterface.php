<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\TokenBuffer;

interface BufferEditorInterface
{
    public function editBuffer(TokenBuffer $buffer);
}
