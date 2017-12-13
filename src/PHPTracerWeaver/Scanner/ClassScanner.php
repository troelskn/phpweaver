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

            return;
        }

        if ($token->isA(T_STRING) && 1 === $this->state) {
            $this->state = 2;
            $this->currentClass = $token->getText();
            $this->currentClassScope = $token->getDepth();
            if (is_callable($this->onClassname)) {
                call_user_func($this->onClassname);
            }

            return;
        }

        if (2 === $this->state && $token->getDepth() > $this->currentClassScope) {
            $this->state = 3;

            return;
        }

        if (3 === $this->state && $token->getDepth() === $this->currentClassScope) {
            $this->state = 0;
            $this->currentClass = null;
            if (is_callable($this->onClassEnd)) {
                call_user_func($this->onClassEnd);
            }

            return;
        }
    }

    public function getCurrentClass()
    {
        return $this->currentClass;
    }
}
