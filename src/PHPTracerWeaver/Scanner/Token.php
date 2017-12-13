<?php namespace PHPTracerWeaver\Scanner;

/** a single token in the source code of a file */
class Token
{
    protected $text;
    protected $token;
    protected $depth;

    public function __construct($text, $token, $depth)
    {
        $this->text = $text;
        $this->token = $token;
        $this->depth = $depth;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function isA($type)
    {
        return $this->getToken() === $type;
    }

    /**
     * @return bool
     */
    public function isCurlyOpen()
    {
        $token = $this->getToken();

        return T_CURLY_OPEN === $token || T_DOLLAR_OPEN_CURLY_BRACES === $token || '{' === $this->getText();
    }
}
