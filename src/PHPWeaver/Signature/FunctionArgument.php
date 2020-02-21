<?php namespace PHPWeaver\Signature;

class FunctionArgument
{
    /** @var int */
    protected $id;
    /** @var ?string */
    protected $name;
    /** @var array<string, true> */
    protected $types = [];

    /**
     * @param int         $id
     * @param string|null $name
     * @param string      $type
     */
    public function __construct(int $id, string $name = null, string $type = null)
    {
        $this->id = $id;
        $this->name = $name;

        if (null !== $type) {
            $this->types[$type] = true;
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isUndefined(): bool
    {
        return !$this->types;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->isUndefined()) {
            return 'mixed';
        }

        $types = $this->types;

        // Falsable to bool
        if (isset($types['false']) && (isset($types['bool']) || 1 === count($types))) {
            unset($types['false']);
            $types['bool'] = true;
        }

        $types = $this->orderTypes($types);

        $types = array_keys($types);

        return implode('|', $types);
    }

    private function orderTypes(array $types): array
    {
        ksort($types);

        // False should always be at the end
        if (isset($types['false'])) {
            unset($types['false']);
            $types['false'] = true;
        }

        // Null should always be at the end
        if (isset($types['null'])) {
            unset($types['null']);
            $types['null'] = true;
        }

        return $types;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function collateWith(string $type): void
    {
        if ('???' === $type) {
            return;
        }

        $this->types[$type] = true;
    }

    /**
     * @return string
     */
    public function export(): string
    {
        /** @psalm-suppress PossiblyNullOperand */
        return $this->getName() . ' (' . ($this->isUndefined() ? 'mixed' : $this->getType()) . ')';
    }
}
