<?php namespace PHPTracerWeaver\Scanner;

/** entity representing a file with sourcecode */
class SourceFile
{
    /** @var TokenStream */
    protected $token_stream;
    protected $hash;
    /** @var FileAccessInterface */
    protected $path;

    /**
     * @param FileAccessInterface $path
     * @param TokenStream         $token_stream
     */
    public function __construct(FileAccessInterface $path, TokenStream $token_stream)
    {
        $this->path = $path;
        $this->token_stream = $token_stream;
        $this->hash = $token_stream->getHash();
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
        return $this->hash != $this->token_stream->getHash();
    }

    /**
     * @return TokenStream
     */
    public function getTokenStream(): TokenStream
    {
        return $this->token_stream;
    }

    /**
     * @param TokenStream $token_stream
     *
     * @return void
     */
    public function setTokenStream(TokenStream $token_stream): void
    {
        $this->token_stream = $token_stream;
    }
}
