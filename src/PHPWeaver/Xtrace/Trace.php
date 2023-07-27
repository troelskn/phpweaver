<?php namespace PHPWeaver\Xtrace;

class Trace
{
    public string $function;
    /** @var array<int, string> */
    public array $arguments;
    public bool $exited;
    public string $returnValue = 'void';

    /**
     * @param array<int, string> $arguments
     */
    public function __construct(string $function, array $arguments, bool $exited)
    {
        $this->function = $function;
        $this->arguments = $arguments;
        $this->exited = $exited;
    }
}
