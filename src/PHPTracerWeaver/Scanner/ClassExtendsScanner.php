<?php namespace PHPTracerWeaver\Scanner;

/** Scans for class inheritance */
class ClassExtendsScanner implements ScannerInterface
{
    protected $onExtends;
    protected $onImplements;
    /** @var int */
    protected $state = 0;
    /** @var ClassScanner */
    protected $classScanner;

    public function __construct(ClassScanner $classScanner)
    {
        $this->classScanner = $classScanner;
    }

    public function notifyOnExtends($callback)
    {
        $this->onExtends = $callback;
    }

    public function notifyOnImplements($callback)
    {
        $this->onImplements = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_EXTENDS)) {
            $this->state = 1;
        } elseif ($token->isA(T_IMPLEMENTS)) {
            $this->state = 2;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->onExtends)) {
                call_user_func($this->onExtends, $this->classScanner->getCurrentClass(), $token->getText());
            }
        } elseif (2 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->onImplements)) {
                call_user_func($this->onImplements, $this->classScanner->getCurrentClass(), $token->getText());
            }
        } elseif ($token->isCurlyOpen()) {
            $this->state = 0;
        }
    }
}
