<?php namespace PHPTracerWeaver\Xtrace;

class FunctionTracer
{
    /** @var TraceSignatureLogger */
    protected $handler;
    /** @var array[] */
    protected $stack = [];
    /** @var string[] */
    protected $internalFunctions;
    /** @var int */
    private $currentDepth = 0;

    /**
     * @param TraceSignatureLogger $handler
     */
    public function __construct(TraceSignatureLogger $handler)
    {
        $this->handler = $handler;
        $definedFunctions = get_defined_functions(false);

        $this->internalFunctions = array_merge(
            $definedFunctions['internal'],
            ['{main}', 'include', 'include_once', 'require', 'require_once']
        );
    }

    /**
     * @return void
     */
    public function traceStart(): void
    {
    }

    /**
     * @return void
     */
    public function traceEnd(): void
    {
    }

    /**
     * @param array $trace
     *
     * @return void
     */
    public function functionCall(array $trace): void
    {
        $this->currentDepth = $trace['depth'];
        $this->stack[] = $trace;
    }

    /**
     * Close any function that was implicilty closed by given depth
     *
     * @param int $depth
     *
     * @return void
     */
    public function closeVoidReturns(int $depth): void
    {
        while ($this->stack && $depth <= $this->currentDepth) {
            $this->returnValue();
        }
    }

    /**
     * Match a return value with the function call and log it
     *
     * Note: The optimizer will remove unused retun values making them look like VOID returns
     *
     * @param string $value
     *
     * @return void
     */
    public function returnValue(string $value = 'VOID'): void
    {
        $functionCall = array_pop($this->stack);

        $functionCall['returnValue'] = $value;
        if (!in_array($functionCall['function'], $this->internalFunctions, true)) {
            $this->handler->log($functionCall);
        }
        $this->currentDepth = end($this->stack)['depth'] ?? 0;
    }
}
