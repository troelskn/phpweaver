<?php namespace PHPWeaver\Signature;

use PHPWeaver\Reflector\ClassCollatorInterface;

class FunctionSignature
{
    /** @var array<int, FunctionArgument> */
    protected array $arguments = [];
    protected FunctionArgument $returnType;

    public function __construct()
    {
        $this->returnType = new FunctionArgument();
    }

    /**
     * @param array<int, string> $arguments
     */
    public function blend(array $arguments, string $returnType): void
    {
        foreach ($arguments as $id => $type) {
            $arg = $this->getArgumentById($id);
            $arg->collateWith($type);
        }

        if ($returnType) {
            $this->returnType->collateWith($returnType);
        }
    }

    public function getReturnType(): string
    {
        return $this->returnType->getType();
    }

    public function getArgumentById(int $id): FunctionArgument
    {
        if (!isset($this->arguments[$id])) {
            $this->arguments[$id] = new FunctionArgument();
        }

        return $this->arguments[$id];
    }

    /**
     * @return array<int, FunctionArgument>
     */
    public function getArguments(): array
    {
        $args = $this->arguments;
        ksort($args);

        return $args;
    }
}
