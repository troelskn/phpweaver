<?php namespace PHPWeaver\Scanner;

use PHPWeaver\Exceptions\Exception;

/** default implementation for FileAccess */
class FilePath implements FileAccessInterface
{
    /** @var string */
    protected $pathname;

    /**
     * @param string $pathname
     */
    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        if (!is_file($this->getPathname())) {
            throw new Exception('Not a file or not readable');
        }

        return file_get_contents($this->getPathname()) ?: '';
    }

    /**
     * @return string
     */
    public function getPathname(): string
    {
        return realpath($this->pathname);
    }
}
