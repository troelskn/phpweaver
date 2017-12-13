<?php namespace PHPTracerWeaver\Scanner;

/** a repository + gateway for SourceFile's */
class SourceFileRepository
{
    protected $streams = [];
    protected $parser;

    public function __construct(TokenStreamParser $parser)
    {
        $this->parser = $parser;
    }

    public function get(FileAccessInterface $path)
    {
        if (!isset($this->streams[$path->getPathname()])) {
            $this->streams[$path->getPathname()] = $this->load($path);
        }
        // todo: assert not changed on disk
        return $this->streams[$path->getPathname()];
    }

    protected function load(FileAccessInterface $path)
    {
        return new SourceFile($path, $this->parser->scan($path->getContents(), $path->getPathname()));
    }
}
