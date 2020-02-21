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

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
    {
        if ($token->isA(T_INTERFACE) || $token->isA(T_CLASS)) {
            $this->state = 1;

            return;
        }

        if ($token->isA(T_STRING) && 1 === $this->state) {
            $this->state = 2;
            $this->currentClass = $token->getText();
            $this->currentClassScope = $token->getDepth();

            return;
        }

        if (2 === $this->state && $token->getDepth() > $this->currentClassScope) {
            $this->state = 3;

            return;
        }

        if (3 === $this->state && $token->getDepth() === $this->currentClassScope) {
            $this->state = 0;
            $this->currentClass = '';

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
