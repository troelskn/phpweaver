<?php namespace PHPWeaver\Signature;

use PHPWeaver\Reflector\ClassCollatorInterface;

class FunctionSignature
{
    /** @var FunctionArgument[] */
    protected $arguments = [];
    /** @var FunctionArgument */
    protected $returnType;
    /** @var ClassCollatorInterface */
    protected $collator;

    /**
     * @param ClassCollatorInterface $collator
     */
    public function __construct(ClassCollatorInterface $collator)
    {
        $this->collator = $collator;
        $this->returnType = new FunctionArgument(0);
    }

    /**
     * @param string[] $arguments
     * @param string   $returnType
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
            $this->arguments[$id] = new FunctionArgument($id);
        }

        return $this->arguments[$id];
    }

    /**
     * @return FunctionArgument[]
     */
    public function getArguments(): array
    {
        $args = $this->arguments;
        ksort($args);

        return $args;
    }
}
