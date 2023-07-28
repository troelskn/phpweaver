<?php namespace PHPWeaver\Transform;

use PHPWeaver\Scanner\ScannerInterface;

interface TransformerInterface extends ScannerInterface
{
    public function getOutput(): string;
}
