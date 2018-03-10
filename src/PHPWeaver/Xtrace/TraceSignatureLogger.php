<?php namespace PHPWeaver\Xtrace;

use PHPWeaver\Exceptions\Exception;
use PHPWeaver\Signature\Signatures;

class TraceSignatureLogger
{
    /** @var Signatures */
    private $signatures;
    /** @var string[] */
    private $typeMapping = [
        'TRUE'            => 'bool',
        'FALSE'           => 'false', // Falsable or tbd. bool
        'NULL'            => 'null',
        'void'            => 'void',
        '???'             => '???',
        '*uninitialized*' => '???',
        '...'             => 'array',
    ];

    /**
     * @param Signatures $signatures
     */
    public function __construct(Signatures $signatures)
    {
        $this->signatures = $signatures;
    }

    /**
     * @param string[] $trace
     *
     * @return void
     */
    public function log(array $trace): void
    {
        $sig = $this->signatures->get($trace['function']);
        $sig->blend(
            $this->parseArguments($trace['arguments']),
            $this->parseType($trace['returnValue'])
        );
    }

    /**
     * @param string[] $arguments
     *
     * @return string[]
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
     * @param string $type
     *
     * @todo fuzzy type detection (float or int in string)
     *
     * @throws Exception
     *
     * @return string
     */
    public function parseType(string $type): string
    {
        if (isset($this->typeMapping[$type])) {
            return $this->typeMapping[$type];
        }

        $typeTransforms = ['~^(array) \(.*\)$~', '~^class (\S+)~', '~^(resource)\(\d+\)~u'];
        foreach ($typeTransforms as $regex) {
            if (preg_match($regex, $type, $match)) {
                if ('array' === $match[1]) {
                    return $this->getArrayType($type);
                }

                return $match[1];
            }
        }

        if (is_numeric($type)) {
            if (preg_match('~^-?\d+$~u', $type)) {
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
     *
     * @param string $arrayType
     *
     * @return string
     */
    public function getArrayType(string $arrayType): string
    {
        $subTypes = [];
        $elementTypes = $this->getArrayElements($arrayType);
        foreach ($elementTypes as $elementType) {
            $subTypes[$this->parseType($elementType)] = true;
        }

        return $this->formatArrayType($subTypes);
    }

    /**
     * Extract the array elements from an array trace.
     *
     * @param string $type
     *
     * @return array
     */
    private function getArrayElements(string $type): array
    {
        // Remove array wrapper
        preg_match('~^array \((.*?)(?:, )?\.{0,3}\)$~u', $type, $match);
        if (empty($match[1])) {
            return [];
        }

        // Find each string|int key followed by double arrow, taking \' into account
        $rawSubTypes = preg_split('~(?:, |^)(?:(?:\'.+?(?:(?<!\\\\)\')+)|\d) => ~u', $match[1]);
        unset($rawSubTypes[0]); // Remove split at first key

        return array_values($rawSubTypes);
    }

    /**
     * Format an array of types as an array with a sub-type.
     *
     * @todo Find common class/interface/trait for object types
     *
     * @param array $subTypes
     *
     * @return string
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