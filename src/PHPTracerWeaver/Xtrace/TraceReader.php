<?php namespace PHPTracerWeaver\Xtrace;

use PHPTracerWeaver\Exceptions\Exception;
use SplFileObject;

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
     * Process a trace line
     *
     * @param int            $lineNo
     * @param string         $line
     *
     * @return void
     */
    public function processLine(int $lineNo, string $line): void
    {
        $line = trim($line);

        // Blank line
        if (!$line) {
            return;
        }

        // Trace start
        if (preg_match('~TRACE START \[[0-9 :-]+\]~', $line, $match)) {
            $this->handler->traceStart();
            return;
        }

        // Trace end
        if (preg_match('~TRACE END   \[[0-9 :-]+\]~', $line, $match)) {
            $this->handler->closeVoidReturns(0);
            $this->handler->traceEnd();

            return;
        }

        // runtime-generated functions?
        if (preg_match('~^([.\d]+)\s+(\d+)(\s+)-> ([^(]+)\((.*)\) ([^:]+).*:(\d+)$~', $line, $match)) {
            $depth = (strlen($match[3]) - 3) / 2;
            $this->handler->closeVoidReturns($depth);
            $this->handler->functionCall([
                'time'         => $match[1],
                'memory_usage' => $match[2],
                'depth'        => $depth,
                'function'     => $match[4],
                'arguments'    => $match[5],
                'filename'     => $match[6],
                'linenumber'   => $match[7],
            ]);
            return;
        }

        // Return value
        if (preg_match('~^[.\d]+\s+\d+(\s+)>=> (.+)$~', $line, $match)) {
            $depth = (strlen($match[1]) - 4) / 2;
            $this->handler->closeVoidReturns($depth + 1);
            $this->handler->returnValue($match[2]);
            return;
        }

        // dunno what this is?
        if (preg_match('~^[.\d]+\s+\d+$~', $line, $match)) {
            return;
        }

        throw new Exception('Could not parse line ' . $lineNo . ': ' . $line . PHP_EOL);
    }
}
