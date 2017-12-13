<?php namespace PHPTracerWeaver\Scanner;

/** Tracks possible preludes for functions */
class ModifiersScanner implements ScannerInterface
{
    protected $onModifiersBegin;
    protected $onModifiersEnd;
    /** @var bool */
    protected $wasFunction = false;
    /** @var int */
    protected $state = 0;

    public function notifyOnModifiersBegin($callback)
    {
        $this->onModifiersBegin = $callback;
    }

    public function notifyOnModifiersEnd($callback)
    {
        $this->onModifiersEnd = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_PRIVATE)
            || $token->isA(T_PROTECTED)
            || $token->isA(T_PUBLIC)
            || $token->isA(T_FINAL)
            || $token->isA(T_STATIC)
            || $token->isA(T_ABSTRACT)
        ) {
            $this->state = 1;
            if (is_callable($this->onModifiersBegin)) {
                call_user_func($this->onModifiersBegin);
            }
        } elseif ($token->isA(T_INTERFACE)
            || $token->isA(T_CLASS)
            || $token->isA(T_FUNCTION)
            || $token->isA(T_VARIABLE)
        ) {
            $this->wasFunction = $token->isA(T_FUNCTION);
            $this->state = 0;
            if (is_callable($this->onModifiersEnd)) {
                call_user_func($this->onModifiersEnd);
            }
        }
    }

    public function isActive()
    {
        return 1 === $this->state;
    }

    public function wasFunction()
    {
        return $this->wasFunction;
    }
}
