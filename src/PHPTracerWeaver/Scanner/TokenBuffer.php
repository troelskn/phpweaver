<?php namespace PHPTracerWeaver\Scanner;

/** Used by transformers */
class TokenBuffer
{
    protected $super;
    protected $tokens = [];

    public function __construct(self $super = null)
    {
        $this->super = $super;
    }

    public function prepend(Token $token)
    {
        array_unshift($this->tokens, $token);
    }

    public function append(Token $token)
    {
        $this->tokens[] = $token;
    }

    public function getFirstToken()
    {
        return isset($this->tokens[0]) ? $this->tokens[0] : null;
    }

    public function replaceToken(Token $token, Token $new_token)
    {
        $tmp = [];
        foreach ($this->tokens as $t) {
            if ($t === $token) {
                $tmp[] = $new_token;
            } else {
                $tmp[] = $t;
            }
        }
        $this->tokens = $tmp;
    }

    public function hasSuper()
    {
        return (bool) $this->super;
    }

    public function raise()
    {
        return new self($this);
    }

    public function flush()
    {
        if (!$this->super) {
            return $this;
        }
        $tokens = $this->tokens;
        $this->tokens = [];
        foreach ($tokens as $token) {
            $this->super->append($token);
        }

        return $this->super;
    }

    public function toText()
    {
        $out = '';
        foreach ($this->tokens as $token) {
            $out .= $token->getText();
        }

        return $out;
    }
}
