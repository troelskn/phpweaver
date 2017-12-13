<?php namespace PHPTracerWeaver\Scanner;

/** provides access to a file */
interface FileAccessInterface
{
    public function getContents();

    public function getPathname();
}
