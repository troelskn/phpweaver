<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\ScannerInterface;

interface TransformerInterface extends ScannerInterface
{
    public function getOutput();
}
