<?php namespace PHPWeaver\Scanner;

/** a collection of tokens */
class TokenStream
{
    /** @var Token[] */
    protected array $tokens = [];

    public function append(Token $token): void
    {
        $this->tokens[] = $token;
    }

    public function iterate(ScannerInterface $scanner): void
    {
        foreach ($this->tokens as $token) {
            $scanner->accept($token);
        }
    }
}
