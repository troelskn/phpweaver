<?php namespace PHPWeaver\Scanner;

/** Tracks the current class scope */
class ClassScanner implements ScannerInterface
{
    protected int $currentClassScope = 0;
    protected string $currentClass = '';
    protected int $state = 0;

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

    public function getCurrentClass(): string
    {
        return $this->currentClass;
    }
}
