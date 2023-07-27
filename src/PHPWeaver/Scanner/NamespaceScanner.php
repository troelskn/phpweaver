<?php namespace PHPWeaver\Scanner;

/** Tracks possible preludes for functions */
class NamespaceScanner implements ScannerInterface
{
    protected int $state = 0;
    private string $currentNamespace = '';

    public function accept(Token $token): void
    {
        if ($token->isA(T_NAMESPACE)) {
            $this->state = 1;
            $this->currentNamespace = '';

            return;
        }

        if (1 === $this->state && $token->isA(T_STRING)) {
            $this->currentNamespace .= $token->getText() . '\\';

            return;
        }

        if (1 === $this->state && $token->isA(-1)) {
            $this->state = 0;

            return;
        }
    }

    public function getCurrentNamespace(): string
    {
        return $this->currentNamespace;
    }
}
