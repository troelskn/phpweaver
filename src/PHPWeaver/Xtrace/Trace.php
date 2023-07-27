<?php namespace PHPWeaver\Xtrace;

class Trace
{
    /** @var string */
    public $function;
    /** @var array<int, string> */
    public $arguments;
    /** @var bool */
    public $exited;
    /** @var string */
    public $returnValue = 'void';

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
