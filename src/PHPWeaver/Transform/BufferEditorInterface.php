<?php namespace PHPWeaver\Transform;

use PHPWeaver\Scanner\TokenBuffer;

interface BufferEditorInterface
{
    public function editBuffer(TokenBuffer $buffer): void;
}
