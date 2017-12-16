<?php namespace PHPTracerWeaver\Signature;

class FunctionArgument
{
    /** @var int */
    protected $id;
    /** @var ?string */
    protected $name;
    /** @var string */
    protected $type;

    /**
     * @param int         $id
     * @param string|null $name
     * @param string      $type
     */
    public function __construct(int $id, string $name = null, string $type = '???')
    {
        if ('null' === $type) {
            $type = '???';
        }

        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
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
        return '???' === $this->type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ('false' === $this->type) { // Not falsable unioun type
            return 'bool';
        }

        return !$this->isUndefined() ? $this->type : 'mixed';
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function collateWith(string $type): void
    {
        if ('???' === $this->type) {
            $this->type = $type;
        }

        if ($type === $this->type || '???' === $type || '' === $type) {
            return;
        }

        $tmp = explode('|', $this->type);
        $tmp = array_filter($tmp);
        $tmp = array_flip($tmp);
        $tmp[$type] = 0;

        // Falsable to bool
        if (isset($tmp['false']) && (isset($tmp['bool']) || 1 === count($tmp))) {
            unset($tmp['false']);
            $tmp['bool'] = 0;
        }

        ksort($tmp);

        // False should always be the last type
        if (isset($tmp['false'])) {
            unset($tmp['false']);
            $tmp['false'] = 0; // Always have false as the last option
        }

        // Null should always be the last type
        if (isset($tmp['null'])) {
            unset($tmp['null']);
            $tmp['null'] = 0; // Always have null as the last option
        }

        $tmp = array_keys($tmp);

        $this->type = implode('|', $tmp);
    }

    /**
     * @return string
     */
    public function export(): string
    {
        return $this->getName() . ' (' . ($this->isUndefined() ? 'mixed' : $this->getType()) . ')';
    }
}
