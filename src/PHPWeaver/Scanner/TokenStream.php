<?php namespace PHPWeaver\Scanner;

/** a collection of tokens */
class TokenStream
{
    /** @var Token[] */
    protected $tokens = [];

    /**
     * @param Token $token
     *
     * @return void
     */
    public function append(Token $token): void
    {
        $this->tokens[] = $token;
    }

    /**
     * @param ScannerInterface $scanner
     *
     * @return void
     */
    public function iterate(ScannerInterface $scanner): void
    {
        foreach ($this->tokens as $token) {
            $scanner->accept($token);
        }
    }
}
