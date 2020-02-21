<?php namespace PHPWeaver\Xtrace;

/**
 * Class for parsing xdebug function trace files.
 */
class TraceReader
{
    /** @var FunctionTracer */
    protected $handler;

    /**
     * @param FunctionTracer $handler
     */
    public function __construct(FunctionTracer $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Process a trace line.
     *
     * @param string $line
     *
     * @return void
     */
    public function processLine(string $line): void
    {
        /** @var array<int, string> */
        $entry = str_getcsv($line, "\t");

        if (!isset($entry[2])) {
            return; // Header or footer
        }

        switch ($entry[2]) {
            case '0':
                if ('0' === $entry[6]) {
                    return; // Internal function
                }

                $this->handler->functionCall((int)$entry[1], $entry[5], array_slice($entry, 11));
                break;
            case '1':
                $this->handler->markCallAsExited((int)$entry[1]);
                break;
            case 'R':
                $this->handler->returnValue((int)$entry[1], $entry[5]);
                break;
        }
    }
}
