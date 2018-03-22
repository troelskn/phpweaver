<?php namespace PHPWeaver\Scanner;

/** Scans for class inheritance */
class ClassExtendsScanner implements ScannerInterface
{
    /** @var ?callable */
    protected $onExtends;
    /** @var ?callable */
    protected $onImplements;
    /** @var int */
    protected $state = 0;
    /** @var ClassScanner */
    protected $classScanner;

    /**
     * @param ClassScanner $classScanner
     */
    public function __construct(ClassScanner $classScanner)
    {
        $this->classScanner = $classScanner;
    }

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnExtends(?callable $callback): void
    {
        $this->onExtends = $callback;
    }

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnImplements(?callable $callback): void
    {
        $this->onImplements = $callback;
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
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
