<?php

class MockPassthruBufferEditor extends PassthruBufferEditor
{
    public $buffer;

    public function editBuffer(TokenBuffer $buffer)
    {
        $this->buffer = clone $buffer;
    }
}
