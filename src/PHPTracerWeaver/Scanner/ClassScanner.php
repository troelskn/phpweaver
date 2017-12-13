<?php namespace PHPTracerWeaver\Scanner;

/** Tracks the current class scope */
class ClassScanner implements ScannerInterface
{
    /** @var int */
    protected $currentClassScope = 0;
    protected $currentClass;
    /** @var int */
    protected $state = 0;
    protected $onClassBegin;
    protected $onClassEnd;
    protected $onClassname;

    public function notifyOnClassBegin($callback)
    {
        $this->onClassBegin = $callback;
    }

    public function notifyOnClassEnd($callback)
    {
        $this->onClassEnd = $callback;
    }

    public function notifyOnClassName($callback)
    {
        $this->onClassname = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_INTERFACE) || $token->isA(T_CLASS)) {
            $this->state = 1;
            if (is_callable($this->onClassBegin)) {
                call_user_func($this->onClassBegin);
            }
        } elseif ($token->isA(T_STRING) && 1 === $this->state) {
            $this->currentClass = $token->getText();
            $this->currentClassScope = $token->getDepth();
            $this->state = 2;
            if (is_callable($this->onClassname)) {
                call_user_func($this->onClassname);
            }
        } elseif (2 === $this->state && $token->getDepth() > $this->currentClassScope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->currentClassScope) {
            $this->currentClass = null;
            $this->state = 0;
            if (is_callable($this->onClassEnd)) {
                call_user_func($this->onClassEnd);
            }
        }
    }

    public function getCurrentClass()
    {
        return $this->currentClass;
    }
}
