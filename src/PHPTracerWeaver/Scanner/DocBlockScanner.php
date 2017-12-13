<?php namespace PHPTracerWeaver\Scanner;

/** Scans for doc-comments */
class DocBlockScanner implements ScannerInterface
{
    protected $last_doc_block;
    /** @var int */
    protected $state = 0;
    /** @var FunctionParametersScanner */
    protected $parameters_scanner;

    public function __construct(FunctionParametersScanner $parameters_scanner)
    {
        $this->parameters_scanner = $parameters_scanner;
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->last_doc_block = $token->getText();
            $this->state = 1;
        } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || $token->isA(T_FUNCTION) || ($token->isA(T_VARIABLE) && !$this->parameters_scanner->isActive())) {
            if (1 === $this->state) {
                $this->state = 2;
            } else {
                $this->last_doc_block = null;
                $this->state = 0;
            }
        }
    }

    public function isActive()
    {
        return 1 === $this->state;
    }

    public function getCurrentDocBlock()
    {
        return $this->last_doc_block;
    }
}
