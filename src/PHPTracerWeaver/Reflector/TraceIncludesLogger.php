<?php namespace PHPTracerWeaver\Reflector;

class TraceIncludesLogger
{
    /** @var StaticReflector */
    protected $reflector;
    protected $includes = [];

    public function __construct(StaticReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function log($trace)
    {
        $filename = $trace['filename'] ?? '';
        if (!isset($this->includes[$filename]) && is_file($filename)) {
            $this->reflector->scanFile($filename);
        }
        $this->includes[$filename] = true;
    }

    public function log_include($trace)
    {
        $this->log($trace);
    }
}
