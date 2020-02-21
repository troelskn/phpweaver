<?php namespace PHPWeaver\Xtrace;

class FunctionTracer
{
    /** @var TraceSignatureLogger */
    protected $handler;
    /** @var array<int, array<string, mixed>> */
    protected $stack = [];
    /** @var string[] */
    protected $internalFunctions = ['{main}', 'eval', 'include', 'include_once', 'require', 'require_once'];

    /**
     * @param TraceSignatureLogger $handler
     */
    public function __construct(TraceSignatureLogger $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param array<int, string> $arguments
     * @return void
     */
    public function functionCall(int $id, string $function, array $arguments): void
    {
        $this->closeVoidReturn();

        if (in_array($function, $this->internalFunctions, true)) {
            return;
        }

        $this->stack[$id] = ['function' => $function, 'arguments' => $arguments, 'exited' => false];
    }

    /**
     * Set exit to true for the given call id.
     *
     * @param int $id
     *
     * @return void
     */
    public function markCallAsExited(int $id): void
    {
        $this->closeVoidReturn();

        if (!isset($this->stack[$id])) {
            return;
        }

        $this->stack[$id]['exited'] = true;
    }

    /**
     * Match a return value with the function call and log it.
     *
     * Note: The optimizer will remove unused retun values making them look like void returns
     *
     * @param int    $id
     * @param string $value
     *
     * @return void
     */
    public function returnValue(int $id, string $value = 'void'): void
    {
        if (!isset($this->stack[$id])) {
            return;
        }

        $functionCall = $this->stack[$id];
        unset($this->stack[$id]);

        $functionCall['returnValue'] = $value;
        $this->handler->log($functionCall);
    }

    /**
     * Set void as return type for prvious call if it has already exitede.
     *
     * @return void
     */
    private function closeVoidReturn(): void
    {
        $last = end($this->stack);
        $key = key($this->stack);
        if (false === $last || null === $key) {
            return;
        }

        if (!empty($last['exited'])) {
            $this->returnValue($key);
        }
    }
}
