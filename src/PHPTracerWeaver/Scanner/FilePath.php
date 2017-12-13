<?php namespace PHPTracerWeaver\Scanner;

/** default implementation for FileAccess */
class FilePath implements FileAccessInterface
{
    protected $pathname;

    public function __construct($pathname)
    {
        $this->pathname = $pathname;
    }

    public function getContents()
    {
        if (!is_file($this->getPathname())) {
            throw new Exception('Not a file or not readable');
        }

        return file_get_contents($this->getPathname());
    }

    public function getPathname()
    {
        return realpath($this->pathname);
    }
}
