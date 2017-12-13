<?php namespace PHPTracerWeaver\Scanner;

/** a collection of tokens */
class TokenStream
{
    /** @var Token[] */
    protected $tokens = [];

    /**
     * @return string
     */
    public function getHash(): string
    {
        return md5(serialize($this->tokens));
    }

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
