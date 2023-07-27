<?php namespace PHPWeaver\Scanner;

/** a single token in the source code of a file */
class Token
{
    protected string $text;
    protected int $token;
    protected int $depth;

    public function __construct(string $text, int $token, int $depth)
    {
        $this->text = $text;
        $this->token = $token;
        $this->depth = $depth;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getToken(): int
    {
        return $this->token;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function isA(int $type): bool
    {
        return $this->getToken() === $type;
    }
}
