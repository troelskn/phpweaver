<?php namespace PHPWeaver\Xtrace;

class FunctionTracer
{
    protected TraceSignatureLogger $handler;
    /** @var array<int, Trace> */
    protected array $stack = [];
    /** @var string[] */
    protected array $internalFunctions = ['{main}', 'eval', 'include', 'include_once', 'require', 'require_once'];

    public function __construct(TraceSignatureLogger $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param array<int, string> $arguments
     */
    public function functionCall(int $id, string $function, array $arguments): void
    {
        $this->closeVoidReturn();

        if (in_array($function, $this->internalFunctions, true)) {
            return;
        }

        $this->stack[$id] = new Trace($function, $arguments, false);
    }

    /**
     * Set exit to true for the given call id.
     */
    public function markCallAsExited(int $id): void
    {
        $this->closeVoidReturn();

        if (!key_exists($id, $this->stack)) {
            return;
        }

        $this->stack[$id]->exited = true;
    }

    /**
     * Match a return value with the function call and log it.
     *
     * Note: The optimizer will remove unused retun values making them look like void returns
     */
    public function returnValue(int $id, string $value = 'void'): void
    {
        if (!key_exists($id, $this->stack)) {
            return;
        }

        $functionCall = $this->stack[$id];
        unset($this->stack[$id]);

        $functionCall->returnValue = $value;
        $this->handler->log($functionCall);
    }

    /**
     * Set void as return type for prvious call if it has already exitede.
     */
    private function closeVoidReturn(): void
    {
        $last = end($this->stack);
        $key = key($this->stack);
        if (false === $last || null === $key) {
            return;
        }

        if ($last->exited) {
            $this->returnValue($key);
        }
    }
}
