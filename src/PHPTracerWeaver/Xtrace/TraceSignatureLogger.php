<?php namespace PHPTracerWeaver\Xtrace;

use PHPTracerWeaver\Reflector\StaticReflector;
use PHPTracerWeaver\Signature\Signatures;

class TraceSignatureLogger
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
