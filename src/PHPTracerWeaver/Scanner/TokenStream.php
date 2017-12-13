<?php namespace PHPTracerWeaver\Scanner;

/** a collection of tokens */
class TokenStream
{
    protected $tokens = [];

    public function getHash()
    {
        return md5(serialize($this->tokens));
    }

    public function append(Token $token)
    {
        $this->tokens[] = $token;
    }

    public function iterate(ScannerInterface $scanner)
    {
        foreach ($this->tokens as $token) {
            $scanner->accept($token);
        }
    }
}
