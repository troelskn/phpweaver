<?php namespace PHPTracerWeaver\Scanner;

/** a repository + gateway for SourceFile's */
class SourceFileRepository
{
    /** @var SourceFile[] */
    protected $streams = [];
    /** @var TokenStreamParser */
    protected $parser;

    /**
     * @param TokenStreamParser $parser
     */
    public function __construct(TokenStreamParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param FileAccessInterface $path
     *
     * @return SourceFile
     */
    public function get(FileAccessInterface $path): SourceFile
    {
        if (!isset($this->streams[$path->getPathname()])) {
            $this->streams[$path->getPathname()] = $this->load($path);
        }
        // todo: assert not changed on disk
        return $this->streams[$path->getPathname()];
    }

    /**
     * @param FileAccessInterface $path
     *
     * @return SourceFile
     */
    protected function load(FileAccessInterface $path): SourceFile
    {
        return new SourceFile($path, $this->parser->scan($path->getContents(), $path->getPathname()));
    }
}
