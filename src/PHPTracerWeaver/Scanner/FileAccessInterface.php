<?php namespace PHPTracerWeaver\Scanner;

/** provides access to a file */
interface FileAccessInterface
{
    /**
     * @return string
     */
    public function getContents(): string;

    /**
     * @return string
     */
    public function getPathname(): string;
}
