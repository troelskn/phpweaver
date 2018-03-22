<?php namespace PHPWeaver\Transform;

use PHPWeaver\Scanner\ScannerInterface;

interface TransformerInterface extends ScannerInterface
{
    /**
     * @return string
     */
    public function getOutput(): string;
}
