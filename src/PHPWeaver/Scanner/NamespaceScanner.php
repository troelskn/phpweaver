<?php namespace PHPWeaver\Scanner;

/** Tracks possible preludes for functions */
class NamespaceScanner implements ScannerInterface
{
    /** @var ?callable */
    protected $onModifiersEnd;
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
            if (is_callable($this->onModifiersEnd)) {
                call_user_func($this->onModifiersEnd);
            }

            return;
        }

        if (1 === $this->state && $token->isA(T_STRING)) {
            $this->currentNamespace .= $token->getText() . '\\';
            if (is_callable($this->onModifiersEnd)) {
                call_user_func($this->onModifiersEnd);
            }

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
