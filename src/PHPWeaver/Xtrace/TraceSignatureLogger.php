<?php namespace PHPWeaver\Xtrace;

use PHPWeaver\Exceptions\Exception;
use PHPWeaver\Signature\Signatures;

class TraceSignatureLogger
{
    private Signatures $signatures;
    /** @var array<string, string> */
    private array $typeMapping = [
        'TRUE'            => 'bool',
        'FALSE'           => 'false', // Falsable or tbd. bool
        'NULL'            => 'null',
        'void'            => 'void',
        '???'             => '???',
        '*uninitialized*' => '???',
        '...'             => 'array',
    ];

    public function __construct(Signatures $signatures)
    {
        $this->signatures = $signatures;
    }

    public function log(Trace $trace): void
    {
        $sig = $this->signatures->get($trace->function);
        $sig->blend(
            $this->parseArguments($trace->arguments),
            $this->parseType($trace->returnValue)
        );
    }

    /**
     * @param string[] $arguments
     *
     * @return array<int, string>
     */
    private function parseArguments(array $arguments): array
    {
        $types = [];
        foreach ($arguments as $type) {
            $types[] = $this->parseType($type);
        }

        return $types;
    }

    /**
     * @todo fuzzy type detection (float or int in string)
     *
     * @throws Exception
     */
    public function parseType(string $type): string
    {
        if (isset($this->typeMapping[$type])) {
            return $this->typeMapping[$type];
        }

        $typeTransforms = ['/^(array) \(.*\)$/s', '/^class (\S+)/s', '/^(resource)\(\d+\)/s'];
        foreach ($typeTransforms as $regex) {
            if (preg_match($regex, $type, $match)) {
                if ('array' === $match[1]) {
                    return $this->getArrayType($type);
                }

                return $match[1];
            }
        }

        if (preg_match('/^\[.*\]$/s', $type, $match)) {
            return $this->getArrayType($type, true);
        }

        if (is_numeric($type)) {
            if (preg_match('/^-?\d+$/', $type)) {
                return 'int';
            }

            return 'float';
        }

        if ("'" === substr($type, 0, 1)) {
            return 'string';
        }

        throw new Exception('Unknown type: ' . $type);
    }

    /**
     * Determin the array sub-type.
     */
    public function getArrayType(string $arrayType, bool $xdebug3 = false): string
    {
        $subTypes = [];
        $elementTypes = $this->getArrayElements($arrayType, $xdebug3);
        foreach ($elementTypes as $elementType) {
            $subTypes[$this->parseType($elementType)] = true;
        }

        return $this->formatArrayType($subTypes);
    }

    /**
     * Extract the array elements from an array trace.
     *
     * @return array<int, string>
     */
    private function getArrayElements(string $type, bool $xdebug3 = false): array
    {
        // Remove array wrapper
        if ($xdebug3) {
            preg_match('/^\[(.*?)(?:, )?\.{0,3}\]$/s', $type, $match);
        } else {
            preg_match('/^array \((.*?)(?:, )?\.{0,3}\)$/s', $type, $match);
        }
        if (empty($match[1])) {
            return [];
        }

        // Find each string|int key followed by double arrow, taking \' into account
        $rawSubTypes = preg_split('/(?:, |^)(?:(?:\'.+?(?:(?<!\\\\\\\\)\')+)|\d) => /s', $match[1]);
        if (false === $rawSubTypes) {
            throw new Exception('Unable to build regex');
        }
        unset($rawSubTypes[0]); // Remove split at first key

        return array_values($rawSubTypes);
    }

    /**
     * Format an array of types as an array with a sub-type.
     *
     * @todo Find common class/interface/trait for object types
     *
     * @param array<string, true> $subTypes
     */
    private function formatArrayType(array $subTypes): string
    {
        if (!$subTypes) {
            return 'array';
        }

        ksort($subTypes);
        $type = implode('|', array_keys($subTypes));
        if (count($subTypes) > 1) {
            $type = '(' . $type . ')';
        }

        return $type . '[]';
    }
}
