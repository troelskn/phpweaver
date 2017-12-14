<?php namespace PHPTracerWeaver\Scanner;

/** Scans for function name + body */
class FunctionBodyScanner implements ScannerInterface
{
    /** @var int */
    protected $currentClassScope;
    /** @var string */
    protected $name = '';
    /** @var int */
    protected $state = 0;

    /**
     * @param Token $token
     *
     * @return void
     */
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

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->state > 2;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
