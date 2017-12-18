<?php namespace PHPWeaver\Scanner;

/** Tracks the current class scope */
class ClassScanner implements ScannerInterface
{
    /** @var int */
    protected $currentClassScope = 0;
    /** @var string */
    protected $currentClass = '';
    /** @var int */
    protected $state = 0;
    /** @var ?callable */
    protected $onClassBegin;
    /** @var ?callable */
    protected $onClassEnd;
    /** @var ?callable */
    protected $onClassname;

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnClassBegin(?callable $callback): void
    {
        $this->onClassBegin = $callback;
    }

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnClassEnd(?callable $callback): void
    {
        $this->onClassEnd = $callback;
    }

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnClassName(?callable $callback): void
    {
        $this->onClassname = $callback;
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
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
            $this->currentClass = '';
            if (is_callable($this->onClassEnd)) {
                call_user_func($this->onClassEnd);
            }

            return;
        }
    }

    /**
     * @return string
     */
    public function getCurrentClass(): string
    {
        return $this->currentClass;
    }
}
