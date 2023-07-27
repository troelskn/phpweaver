<?php namespace PHPWeaver\Signature;

use PHPWeaver\Reflector\ClassCollatorInterface;

class FunctionSignature
{
    /** @var array<int, FunctionArgument> */
    protected $arguments = [];
    /** @var FunctionArgument */
    protected $returnType;

    public function __construct()
    {
        $this->returnType = new FunctionArgument();
    }

    /**
     * @param array<int, string> $arguments
     * @param string             $returnType
     *
     * @return void
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

    /**
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType->getType();
    }

    /**
     * @param int $id
     *
     * @return FunctionArgument
     */
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
