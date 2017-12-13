<?php

/**
 * Class for parsing xdebug function trace files.
 */
class XtraceTraceReader
{
    /** @var SplFileObject */
    protected $file;

    public function __construct(SplFileObject $file)
    {
        $this->file = $file;
    }

    public function process(XtraceFunctionTracer $handler): void
    {
        foreach ($this->file as $lineNo => $line) {
            $line = trim($line);

            // Blank line
            if (!$line) {
                continue;
            }

            // Trace start
            if (preg_match('~TRACE START \[([0-9 :-]+)\]~', $line, $match)) {
                $handler->traceStart($match[1]);
                continue;
            }

            // Trace end
            if (preg_match('~TRACE END   \[([0-9 :-]+)\]~', $line, $match)) {
                $handler->traceEnd($match[1]);

                return;
            }

            // runtime-generated functions?
            if (preg_match('~^([.\d]+)\s+(\d+)(\s+)-> ([^(]+)\((.*)\)(?:\'d)?\s+([^:]+):([0-9]+)$~', $line, $match)) {
                $handler->functionCall([
                    'time'         => $match[1],
                    'memory_usage' => $match[2],
                    'depth'        => (strlen($match[3]) - 3) / 2,
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
                $handler->returnValue($depth, $match[2]);
                continue;
            }

            // dunno what this is?
            if (preg_match('~^[.\d]+\s+\d+$~', $line, $match)) {
                continue;
            }

            throw new Exception('Could not parse line ' . $lineNo . ': ' . $line . "\n");
        }

        $handler->traceEnd();
    }
}

class XtraceFunctionTracer
{
    /** @var XtraceTraceSignatureLogger */
    protected $handler;
    /** @var array */
    protected $stack = [];
    /** @var array */
    protected $internalFunctions;

    public function __construct(XtraceTraceSignatureLogger $handler)
    {
        $this->handler = $handler;
        $definedFunctions = get_defined_functions();

        $this->internalFunctions = array_merge(
            $definedFunctions['internal'],
            ['include', 'include_once', 'require', 'require_once']
        );
    }

    public function traceStart(string $time): void
    {
    }

    public function traceEnd(string $time = null): void
    {
        $this->returnValue(0);
    }

    public function functionCall(array $trace): void
    {
        $this->stack[] = $trace;
    }

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

class XtraceTraceSignatureLogger
{
    /** @var Signatures */
    protected $signatures;
    /** @var StaticReflector|null */
    protected $reflector;
    /** @var array */
    protected $includes = [];

    public function __construct(Signatures $signatures, StaticReflector $reflector = null)
    {
        $this->signatures = $signatures;
        $this->reflector = $reflector;
    }

    public function log(array $trace): void
    {
        if ($this->reflector) {
            $filename = $trace['filename'] ?? '';
            if (!isset($this->includes[$filename]) && is_file($filename)) {
                $this->reflector->scanFile($filename);
            }
            $this->includes[$filename] = true;
        }
        $sig = $this->signatures->get($trace['function']);
        $sig->blend(
            $this->parseArguments($trace['arguments']),
            $this->parseReturnType($trace['returnValue'])
        );
    }

    public function parseArguments(string $asString): array
    {
        if (!$asString) {
            return [];
        }

        $typeTransforms = ['~^(string)\([0-9]+\)$~', '~^(array)\([0-9]+\)$~', '~^class (.+)$~'];
        $typeAliases = ['long' => 'int', 'double' => 'float'];
        // todo: resources ..
        $types = [];
        foreach (explode(', ', $asString) as $type) {
            foreach ($typeTransforms as $regex) {
                if (preg_match($regex, $type, $match)) {
                    $type = $match[1];
                    break;
                }
            }

            $types[] = $typeAliases[$type] ?? $type;
        }

        return $types;
    }

    public function parseReturnType(string $returnValue): string
    {
        // todo: numbers, resources ..
        if ('TRUE' === $returnValue || 'FALSE' === $returnValue) {
            return 'bool';
        }
        if ('NULL' === $returnValue) {
            return 'null';
        }
        if ('VOID' === $returnValue) {
            return 'void';
        }
        if ("'" === substr($returnValue, 0, 1)) {
            return 'string';
        }
        if ('array' === substr($returnValue, 0, 5)) {
            return 'array';
        }
        if (preg_match('~^class (\w+)~', $returnValue, $match)) {
            return $match[1];
        }
        if (preg_match('~^[.0-9]+$~', $returnValue)) {
            return 'float';
        }
        if (preg_match('~^[0-9]+$~', $returnValue)) {
            return 'int';
        }

        throw new Exception('Unknown return value: ' . $returnValue);
    }
}
