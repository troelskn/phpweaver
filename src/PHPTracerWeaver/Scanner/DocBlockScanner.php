<?php namespace PHPTracerWeaver\Scanner;

/** Scans for doc-comments */
class DocBlockScanner implements ScannerInterface
{
    protected $lastDocBlock;
    /** @var int */
    protected $state = 0;
    /** @var FunctionParametersScanner */
    protected $parametersScanner;

    /**
     * @param FunctionParametersScanner $parametersScanner
     */
    public function __construct(FunctionParametersScanner $parametersScanner)
    {
        $this->parametersScanner = $parametersScanner;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->lastDocBlock = $token->getText();
            $this->state = 1;
        } elseif ($token->isA(T_INTERFACE)
            || $token->isA(T_CLASS)
            || $token->isA(T_FUNCTION)
            || ($token->isA(T_VARIABLE) && !$this->parametersScanner->isActive())
        ) {
            if (1 === $this->state) {
                $this->state = 2;

                return;
            }

            $this->lastDocBlock = null;
            $this->state = 0;
        }
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return 1 === $this->state;
    }

    public function getCurrentDocBlock()
    {
        return $this->lastDocBlock;
    }
}
