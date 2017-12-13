<?php namespace PHPTracerWeaver\Scanner;

/** Scans for, collects and parses function signatures */
class FunctionParametersScanner implements ScannerInterface
{
    protected $signature = [];
    /** @var int */
    protected $paren_count = 0;
    /** @var int */
    protected $state = 0;
    protected $onSignatureBegin;
    protected $onSignatureEnd;

    public function notifyOnSignatureBegin($callback)
    {
        $this->onSignatureBegin = $callback;
    }

    public function notifyOnSignatureEnd($callback)
    {
        $this->onSignatureEnd = $callback;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_FUNCTION)) {
            $this->state = 1;
        } elseif (1 === $this->state && '(' === $token->getText()) {
            $this->signature = [];
            $this->signature[] = [$token->getText(), $token->getToken()];
            $this->paren_count = 1;
            $this->state = 2;
            if (is_callable($this->onSignatureBegin)) {
                call_user_func($this->onSignatureBegin);
            }
        } elseif (2 === $this->state) {
            $this->signature[] = [$token->getText(), $token->getToken()];
            if ('(' === $token->getText()) {
                ++$this->paren_count;
            } elseif (')' === $token->getText()) {
                --$this->paren_count;
            }
            if (0 === $this->paren_count) {
                $this->state = 0;
                if (is_callable($this->onSignatureEnd)) {
                    call_user_func($this->onSignatureEnd);
                }
            }
        }
    }

    public function isActive()
    {
        return 0 !== $this->state;
    }

    public function getCurrentSignature()
    {
        return $this->signature;
    }

    public function getCurrentSignatureAsString()
    {
        $txt = '';
        foreach ($this->signature as $struct) {
            $txt .= $struct[0];
        }

        return $txt;
    }

    public function getCurrentSignatureAsTypeMap()
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
