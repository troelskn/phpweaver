<?php namespace PHPWeaver\Scanner;

/** Scans for, collects and parses function signatures */
class FunctionParametersScanner implements ScannerInterface
{
    /** @var array[] */
    protected $signature = [];
    /** @var int */
    protected $parenCount = 0;
    /** @var int */
    protected $state = 0;
    /** @var ?callable */
    protected $onSignatureBegin;
    /** @var ?callable */
    protected $onSignatureEnd;

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnSignatureBegin(?callable $callback): void
    {
        $this->onSignatureBegin = $callback;
    }

    /**
     * @param ?callable $callback
     *
     * @return void
     */
    public function notifyOnSignatureEnd(?callable $callback): void
    {
        $this->onSignatureEnd = $callback;
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
    {
        if ($token->isA(T_FUNCTION)) {
            $this->state = 1;
        } elseif (1 === $this->state && '(' === $token->getText()) {
            $this->signature = [];
            $this->signature[] = [$token->getText(), $token->getToken()];
            $this->parenCount = 1;
            $this->state = 2;
            if (is_callable($this->onSignatureBegin)) {
                call_user_func($this->onSignatureBegin);
            }
        } elseif (2 === $this->state) {
            $this->signature[] = [$token->getText(), $token->getToken()];
            if ('(' === $token->getText()) {
                ++$this->parenCount;
            } elseif (')' === $token->getText()) {
                --$this->parenCount;
            }
            if (0 === $this->parenCount) {
                $this->state = 0;
                if (is_callable($this->onSignatureEnd)) {
                    call_user_func($this->onSignatureEnd);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return 0 !== $this->state;
    }

    /**
     * @return array[]
     */
    public function getCurrentSignature(): array
    {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function getCurrentSignatureAsString(): string
    {
        $txt = '';
        foreach ($this->signature as $struct) {
            $txt .= $struct[0];
        }

        return $txt;
    }

    /**
     * @return string[]
     */
    public function getCurrentSignatureAsTypeMap(): array
    {
        $current = null;
        $map = [];
        foreach ($this->signature as $tuple) {
            [$text, $token] = $tuple;
            if (T_VARIABLE === $token) {
                $map[$text] = $current ? $current : '???';
            } elseif (',' === $text) {
                $current = null;
            } elseif (T_STRING === $token) {
                $current = $text;
            }
        }

        return $map;
    }
}
