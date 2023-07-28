<?php namespace PHPWeaver\Scanner;

/** a statemachine for scanning a tokenstream  */
interface ScannerInterface
{
    public function accept(Token $token): void;
}
