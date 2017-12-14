<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\TokenBuffer;
use PHPTracerWeaver\Transform\PassthruBufferEditor;

class MockPassthruBufferEditor extends PassthruBufferEditor
{
    /** @var TokenBuffer|null */
    public $buffer;

    /**
     * @param TokenBuffer $buffer
     *
     * @return void
     */
    public function editBuffer(TokenBuffer $buffer): void
    {
        $this->buffer = clone $buffer;
    }
}
