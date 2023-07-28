<?php namespace PHPWeaver\Signature;

use PHPWeaver\Exceptions\Exception;
use PHPWeaver\Reflector\ClassCollatorInterface;

class Signatures
{
    /** @var FunctionSignature[] */
    protected array $signaturesArray = [];

    public function has(string $func, string $class = '', string $namespace = ''): bool
    {
        if (!$func) {
            throw new Exception('Illegal identifier: {' . "$func, $class, $namespace" . '}');
        }

        $name = strtolower($namespace . ($class ? $class . '->' : '') . $func);

        return isset($this->signaturesArray[$name]);
    }

    public function get(string $func, string $class = '', string $namespace = ''): FunctionSignature
    {
        if (!$func) {
            throw new Exception('Illegal identifier: {' . "$func, $class, $namespace" . '}');
        }
        $name = strtolower($namespace . ($class ? $class . '->' : '') . $func);
        if (!isset($this->signaturesArray[$name])) {
            $this->signaturesArray[$name] = new FunctionSignature();
        }

        return $this->signaturesArray[$name];
    }
}
