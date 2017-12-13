<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\ScannerInterface;

interface TransformerInterface extends ScannerInterface
{
    /**
     * @return string
     */
    public function getOutput(): string;
}
