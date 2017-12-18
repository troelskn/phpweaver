<?php namespace PHPWeaver\Reflector;

class TraceIncludesLogger
{
    /** @var StaticReflector */
    protected $reflector;
    /** @var bool[] */
    protected $includes = [];

    /**
     * @param StaticReflector $reflector
     */
    public function __construct(StaticReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * @param string[] $trace
     *
     * @return void
     */
    public function log(array $trace): void
    {
        $filename = $trace['filename'] ?? '';
        if (!isset($this->includes[$filename]) && is_file($filename)) {
            $this->reflector->scanFile($filename);
        }
        $this->includes[$filename] = true;
    }

    /**
     * @param string[] $trace
     *
     * @return void
     */
    public function logInclude(array $trace): void
    {
        $this->log($trace);
    }
}
