<?php namespace PHPWeaver\Scanner;

/** Scans for function name + body */
class FunctionBodyScanner implements ScannerInterface
{
    protected int $currentClassScope = 0;
    protected string $name = '';
    protected int $state = 0;

    public function accept(Token $token): void
    {
        if ($token->isA(T_FUNCTION)) {
            $this->currentClassScope = $token->getDepth();
            $this->state = 1;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            $this->name = $token->getText();
            $this->state = 2;
        } elseif (2 === $this->state && $token->getDepth() > $this->currentClassScope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->currentClassScope) {
            $this->state = 0;
        }
    }

    public function isActive(): bool
    {
        return $this->state > 2;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
