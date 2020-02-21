<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\TokenBuffer;
use PHPWeaver\Transform\BufferEditorInterface;

class MockPassthruBufferEditor implements BufferEditorInterface
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
