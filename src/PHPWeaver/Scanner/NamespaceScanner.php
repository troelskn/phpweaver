<?php namespace PHPWeaver\Scanner;

/** Tracks possible preludes for functions */
class NamespaceScanner implements ScannerInterface
{
    /** @var int */
    protected $state = 0;
    /** @var string */
    private $currentNamespace = '';

    /**
     * @param Token $token
     *
     * @return void
     */
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

    /**
     * @return string
     */
    public function getCurrentNamespace(): string
    {
        return $this->currentNamespace;
    }
}
