<?php namespace PHPTracerWeaver\Scanner;

/** Scans for class inheritance */
class ClassExtendsScanner implements ScannerInterface
{
    protected $on_extends;
    protected $on_implements;
    /** @var int */
    protected $state = 0;
    /** @var ClassScanner */
    protected $class_scanner;

    public function __construct(ClassScanner $class_scanner)
    {
        $this->class_scanner = $class_scanner;
    }

    public function notifyOnExtends($callback)
    {
        $this->on_extends = $callback;
    }

    public function notifyOnImplements($callback)
    {
        $this->on_implements = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_EXTENDS)) {
            $this->state = 1;
        } elseif ($token->isA(T_IMPLEMENTS)) {
            $this->state = 2;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->on_extends)) {
                call_user_func($this->on_extends, $this->class_scanner->getCurrentClass(), $token->getText());
            }
        } elseif (2 === $this->state && $token->isA(T_STRING)) {
            if (is_callable($this->on_implements)) {
                call_user_func($this->on_implements, $this->class_scanner->getCurrentClass(), $token->getText());
            }
        } elseif ($token->isCurlyOpen()) {
            $this->state = 0;
        }
    }
}
