<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\TokenBuffer;

class MockPassthruBufferEditor extends PassthruBufferEditor
{
    /** @var TokenBuffer */
    public $buffer;

    public function editBuffer(TokenBuffer $buffer)
    {
        $this->buffer = clone $buffer;
    }
}
