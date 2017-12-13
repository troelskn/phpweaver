<?php namespace PHPTracerWeaver\Xtrace;

class FunctionTracer
{
    /** @var TraceSignatureLogger */
    protected $handler;
    /** @var array[] */
    protected $stack = [];
    /** @var string[] */
    protected $internalFunctions;

    /**
     * @param TraceSignatureLogger $handler
     */
    public function __construct(TraceSignatureLogger $handler)
    {
        $this->handler = $handler;
        $definedFunctions = get_defined_functions();

        $this->internalFunctions = array_merge(
            $definedFunctions['internal'],
            ['include', 'include_once', 'require', 'require_once']
        );
    }

    /**
     * @param string $time
     *
     * @return void
     */
    public function traceStart(string $time): void
    {
    }

    /**
     * @param string $time
     *
     * @return void
     */
    public function traceEnd(string $time = null): void
    {
        $this->returnValue(0);
    }

    /**
     * @param array $trace
     *
     * @return void
     */
    public function functionCall(array $trace): void
    {
        $this->stack[] = $trace;
    }

    /**
     * @param int    $depth
     * @param string $value
     *
     * @return void
     */
    public function returnValue(int $depth, string $value = 'VOID'): void
    {
        $functionCall = array_pop($this->stack);

        $previousCall = end($this->stack);
        if ($previousCall && $depth < $previousCall['depth']) {
            $this->returnValue($previousCall['depth']);
        }

        $functionCall['returnValue'] = $value;
        if (!in_array($functionCall['function'], $this->internalFunctions)) {
            $this->handler->log($functionCall);
        }

        if ($previousCall && $depth === $previousCall['depth']) {
            $this->returnValue($previousCall['depth']);
        }
    }
}
