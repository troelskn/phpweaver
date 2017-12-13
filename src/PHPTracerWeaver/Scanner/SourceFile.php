<?php namespace PHPTracerWeaver\Scanner;

/** entity representing a file with sourcecode */
class SourceFile
{
    protected $token_stream;
    protected $hash;
    protected $path;

    public function __construct(FileAccessInterface $path, TokenStream $token_stream)
    {
        $this->path = $path;
        $this->token_stream = $token_stream;
        $this->hash = $token_stream->getHash();
    }

    public function getPath()
    {
        return $this->path;
    }

    public function hasChanges()
    {
        return $this->hash != $this->token_stream->getHash();
    }

    public function getTokenStream()
    {
        return $this->token_stream;
    }

    public function setTokenStream(TokenStream $token_stream)
    {
        $this->token_stream = $token_stream;
    }
}
