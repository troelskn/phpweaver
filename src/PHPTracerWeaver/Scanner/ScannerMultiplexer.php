<?php namespace PHPTracerWeaver\Scanner;

/** used for sending output to multiple scanners at once */
class ScannerMultiplexer implements ScannerInterface
{
    protected $scanners = [];

    public function appendScanner(ScannerInterface $scanner)
    {
        $this->scanners[] = $scanner;

        return $scanner;
    }

    public function accept(Token $token)
    {
        foreach ($this->scanners as $scanner) {
            $scanner->accept($token);
        }
    }
}
