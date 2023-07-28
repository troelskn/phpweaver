<?php namespace PHPWeaver\Scanner;

/** Tracks possible preludes for functions */
class ModifiersScanner implements ScannerInterface
{
    protected int $state = 0;

    public function accept(Token $token): void
    {
        if ($this->isModifyer($token)) {
            $this->state = 1;

            return;
        }

        if ($this->isModifyable($token)) {
            $this->state = 0;

            return;
        }
    }

    private function isModifyable(Token $token): bool
    {
        return $token->isA(T_INTERFACE)
            || $token->isA(T_CLASS)
            || $token->isA(T_FUNCTION)
            || $token->isA(T_VARIABLE);
    }

    private function isModifyer(Token $token): bool
    {
        return $token->isA(T_PRIVATE)
            || $token->isA(T_PROTECTED)
            || $token->isA(T_PUBLIC)
            || $token->isA(T_FINAL)
            || $token->isA(T_STATIC)
            || $token->isA(T_ABSTRACT);
    }

    public function isActive(): bool
    {
        return 1 === $this->state;
    }
}
