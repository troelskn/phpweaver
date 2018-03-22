<?php namespace PHPWeaver\Scanner;

/** a statemachine for scanning a tokenstream  */
interface ScannerInterface
{
    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void;
}
