<?php namespace PHPTracerWeaver\Xtrace;

use PHPTracerWeaver\Exceptions\Exception;
use SplFileObject;

/**
 * Class for parsing xdebug function trace files.
 */
class TraceReader
{
    /** @var SplFileObject */
    protected $file;

    /**
     * @param SplFileObject $file
     */
    public function __construct(SplFileObject $file)
    {
        $this->file = $file;
    }

    /**
     * @param FunctionTracer $handler
     *
     * @return void
     */
    public function process(FunctionTracer $handler): void
    {
        foreach ($this->file as $lineNo => $line) {
            $line = trim($line);

            // Blank line
            if (!$line) {
                continue;
            }

            // Trace start
            if (preg_match('~TRACE START \[[0-9 :-]+\]~', $line, $match)) {
                $handler->traceStart();
                continue;
            }

            // Trace end
            if (preg_match('~TRACE END   \[[0-9 :-]+\]~', $line, $match)) {
                $handler->closeVoidReturns(0);
                $handler->traceEnd();

                return;
            }

            // runtime-generated functions?
            if (preg_match('~^([.\d]+)\s+(\d+)(\s+)-> ([^(]+)\((.*)\)(?:\'d)?\s+([^:]+):([0-9]+)$~', $line, $match)) {
                $depth = (strlen($match[3]) - 3) / 2;
                $handler->closeVoidReturns($depth);
                $handler->functionCall([
                    'time'         => $match[1],
                    'memory_usage' => $match[2],
                    'depth'        => $depth,
                    'function'     => $match[4],
                    'arguments'    => $match[5],
                    'filename'     => $match[6],
                    'linenumber'   => $match[7],
                ]);
                continue;
            }

            // Return value
            if (preg_match('~^[.\d]+\s+\d+(\s+)>=> (.+)$~', $line, $match)) {
                $depth = (strlen($match[1]) - 4) / 2;
                $handler->closeVoidReturns($depth + 1);
                $handler->returnValue($match[2]);
                continue;
            }

            // dunno what this is?
            if (preg_match('~^[.\d]+\s+\d+$~', $line, $match)) {
                continue;
            }

            throw new Exception('Could not parse line ' . $lineNo . ': ' . $line . PHP_EOL);
        }

        $handler->closeVoidReturns(0);
    }
}
