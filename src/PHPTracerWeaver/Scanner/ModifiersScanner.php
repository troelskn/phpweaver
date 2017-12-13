<?php namespace PHPTracerWeaver\Scanner;

/** Tracks possible preludes for functions */
class ModifiersScanner implements ScannerInterface
{
    protected $on_modifiers_begin;
    protected $on_modifiers_end;
    protected $was_function = false;
    protected $state = 0;

    public function notifyOnModifiersBegin($callback)
    {
        $this->on_modifiers_begin = $callback;
    }

    public function notifyOnModifiersEnd($callback)
    {
        $this->on_modifiers_end = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_PRIVATE) || $token->isA(T_PROTECTED) || $token->isA(T_PUBLIC) || $token->isA(T_FINAL) || $token->isA(T_STATIC) || $token->isA(T_ABSTRACT)) {
            $this->state = 1;
            if (is_callable($this->on_modifiers_begin)) {
                call_user_func($this->on_modifiers_begin);
            }
        } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || $token->isA(T_FUNCTION) || $token->isA(T_VARIABLE)) {
            $this->was_function = $token->isA(T_FUNCTION);
            $this->state = 0;
            if (is_callable($this->on_modifiers_end)) {
                call_user_func($this->on_modifiers_end);
            }
        }
    }

    public function isActive()
    {
        return 1 === $this->state;
    }

    public function wasFunction()
    {
        return $this->was_function;
    }
}
