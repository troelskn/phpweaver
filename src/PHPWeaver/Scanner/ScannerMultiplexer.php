<?php namespace PHPWeaver\Scanner;

/** used for sending output to multiple scanners at once */
class ScannerMultiplexer implements ScannerInterface
{
    /** @var ScannerInterface[] */
    protected array $scanners = [];

    public function appendScanner(ScannerInterface $scanner): void
    {
        $this->scanners[] = $scanner;
    }

    /**
     * @param ScannerInterface[] $scanners
     */
    public function appendScanners(array $scanners): void
    {
        foreach ($scanners as $scanner) {
            $this->appendScanner($scanner);
        }
    }

    public function accept(Token $token): void
    {
        foreach ($this->scanners as $scanner) {
            $scanner->accept($token);
        }
    }
}
