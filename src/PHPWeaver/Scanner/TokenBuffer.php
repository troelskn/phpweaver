<?php namespace PHPWeaver\Scanner;

/** Used by transformers */
class TokenBuffer
{
    /** @var static|null */
    protected $super;
    /** @var Token[] */
    protected $tokens = [];

    /**
     * @param self|null $super
     */
    public function __construct(self $super = null)
    {
        $this->super = $super;
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function prepend(Token $token): void
    {
        array_unshift($this->tokens, $token);
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
     * @return ?Token
     */
    public function getFirstToken(): ?Token
    {
        return isset($this->tokens[0]) ? $this->tokens[0] : null;
    }

    /**
     * @param Token $token
     * @param Token $newToken
     *
     * @return void
     */
    public function replaceToken(Token $token, Token $newToken): void
    {
        $tmp = [];
        foreach ($this->tokens as $existingToken) {
            if ($existingToken === $token) {
                $tmp[] = $newToken;
                continue;
            }

            $tmp[] = $existingToken;
        }

        $this->tokens = $tmp;
    }

    /**
     * @return bool
     */
    public function hasSuper(): bool
    {
        return (bool) $this->super;
    }

    /**
     * @return self
     */
    public function raise(): self
    {
        return new self($this);
    }

    /**
     * @return self
     */
    public function flush(): self
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

    /**
     * @return string
     */
    public function toText(): string
    {
        $out = '';
        foreach ($this->tokens as $token) {
            $out .= $token->getText();
        }

        return $out;
    }
}
