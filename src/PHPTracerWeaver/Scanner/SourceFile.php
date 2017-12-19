<?php namespace PHPTracerWeaver\Scanner;

/** entity representing a file with sourcecode */
class SourceFile
{
    /** @var TokenStream */
    protected $tokenStream;
    /** @var string */
    protected $hash;
    /** @var FileAccessInterface */
    protected $path;

    /**
     * @param FileAccessInterface $path
     * @param TokenStream         $tokenStream
     */
    public function __construct(FileAccessInterface $path, TokenStream $tokenStream)
    {
        $this->path = $path;
        $this->tokenStream = $tokenStream;
        $this->hash = $tokenStream->getHash();
    }

    /**
     * @return FileAccessInterface
     */
    public function getPath(): FileAccessInterface
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function hasChanges(): bool
    {
        return $this->hash != $this->tokenStream->getHash();
    }

    /**
     * @return TokenStream
     */
    public function getTokenStream(): TokenStream
    {
        return $this->tokenStream;
    }

    /**
     * @param TokenStream $tokenStream
     *
     * @return void
     */
    public function setTokenStream(TokenStream $tokenStream): void
    {
        $this->tokenStream = $tokenStream;
    }
}
