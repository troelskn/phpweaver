<?php namespace PHPWeaver\Scanner;

/** a single token in the source code of a file */
class Token
{
    /** @var string */
    protected $text;
    /** @var int */
    protected $token;
    /** @var int */
    protected $depth;

    /**
     * @param string $text
     * @param int    $token
     * @param int    $depth
     */
    public function __construct(string $text, int $token, int $depth)
    {
        $this->text = $text;
        $this->token = $token;
        $this->depth = $depth;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return int
     */
    public function getToken(): int
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isA(int $type): bool
    {
        return $this->getToken() === $type;
    }
}
