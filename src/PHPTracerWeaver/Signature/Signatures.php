<?php namespace PHPTracerWeaver\Signature;

use PHPTracerWeaver\Reflector\ClassCollatorInterface;

class Signatures
{
    protected $signatures_array = [];
    protected $collator;

    public function __construct(ClassCollatorInterface $collator)
    {
        $this->collator = $collator;
    }

    public function has($func, $class = '')
    {
        $name = strtolower($class ? ($class . '->' . $func) : $func);

        return isset($this->signatures_array[$name]);
    }

    public function get($func, $class = '')
    {
        if (!$func) {
            throw new Exception('Illegal identifier: {' . "$func, $class" . '}');
        }
        $name = strtolower($class ? ($class . '->' . $func) : $func);
        if (!isset($this->signatures_array[$name])) {
            $this->signatures_array[$name] = new FunctionSignature($this->collator);
        }

        return $this->signatures_array[$name];
    }

    public function export()
    {
        $out = [];
        foreach ($this->signatures_array as $name => $function_signature) {
            $out[$name] = $function_signature->export();
        }

        return $out;
    }
}
