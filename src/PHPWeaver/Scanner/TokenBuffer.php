<?php namespace PHPWeaver\Scanner;

/** Used by transformers */
class TokenBuffer
{
    /** @var ?static */
    protected ?self $super;
    /** @var Token[] */
    protected array $tokens = [];

    /**
     * @param ?static $super
     */
    public function __construct(?self $super = null)
    {
        $this->super = $super;
    }

    public function prepend(Token $token): void
    {
        array_unshift($this->tokens, $token);
    }

    public function append(Token $token): void
    {
        $this->tokens[] = $token;
    }

    public function getFirstToken(): ?Token
    {
        return isset($this->tokens[0]) ? $this->tokens[0] : null;
    }

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

    public function hasSuper(): bool
    {
        return (bool) $this->super;
    }

    public function raise(): self
    {
        return new self($this);
    }

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

    public function toText(): string
    {
        $out = '';
        foreach ($this->tokens as $token) {
            $out .= $token->getText();
        }

        return $out;
    }
}
