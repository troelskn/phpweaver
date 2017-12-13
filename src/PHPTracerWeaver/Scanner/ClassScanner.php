<?php namespace PHPTracerWeaver\Scanner;

/** Tracks the current class scope */
class ClassScanner implements ScannerInterface
{
    /** @var int */
    protected $current_class_scope = 0;
    protected $current_class;
    /** @var int */
    protected $state = 0;
    protected $on_class_begin;
    protected $on_class_end;
    protected $on_classname;

    public function notifyOnClassBegin($callback)
    {
        $this->on_class_begin = $callback;
    }

    public function notifyOnClassEnd($callback)
    {
        $this->on_class_end = $callback;
    }

    public function notifyOnClassName($callback)
    {
        $this->on_classname = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_INTERFACE) || $token->isA(T_CLASS)) {
            $this->state = 1;
            if (is_callable($this->on_class_begin)) {
                call_user_func($this->on_class_begin);
            }
        } elseif ($token->isA(T_STRING) && 1 === $this->state) {
            $this->current_class = $token->getText();
            $this->current_class_scope = $token->getDepth();
            $this->state = 2;
            if (is_callable($this->on_classname)) {
                call_user_func($this->on_classname);
            }
        } elseif (2 === $this->state && $token->getDepth() > $this->current_class_scope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->current_class_scope) {
            $this->current_class = null;
            $this->state = 0;
            if (is_callable($this->on_class_end)) {
                call_user_func($this->on_class_end);
            }
        }
    }

    public function getCurrentClass()
    {
        return $this->current_class;
    }
}
