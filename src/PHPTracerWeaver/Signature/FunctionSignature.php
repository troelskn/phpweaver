<?php namespace PHPTracerWeaver\Signature;

use PHPTracerWeaver\Reflector\ClassCollatorInterface;

class FunctionSignature
{
    protected $arguments = [];
    /** @var FunctionArgument */
    protected $returnType;
    protected $collator;

    public function __construct(ClassCollatorInterface $collator)
    {
        $this->collator = $collator;
        $this->returnType = new FunctionArgument(0);
    }

    public function blend(array $arguments, string $returnType)
    {
        foreach ($arguments as $id => $type) {
            $arg = $this->getArgumentById($id);
            $arg->collateWith($type);
            if (!$arg->getName()) {
                $arg->setName($id);
            }
        }

        if ($returnType) {
            $this->returnType->collateWith($returnType);
        }
    }

    public function getReturnType(): string
    {
        return $this->returnType->getType();
    }

    public function getArgumentById($id)
    {
        if (!isset($this->arguments[$id])) {
            $this->arguments[$id] = new FunctionArgument($id);
        }

        return $this->arguments[$id];
    }

    public function getArgumentByName($name)
    {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument;
            }
        }
    }

    public function getArguments()
    {
        $args = $this->arguments;
        ksort($args);

        return $args;
    }

    public function export()
    {
        $out = [];
        foreach ($this->arguments as $argument) {
            $out[] = $argument->export();
        }

        return $out;
    }
}
