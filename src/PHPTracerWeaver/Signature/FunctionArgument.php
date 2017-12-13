<?php namespace PHPTracerWeaver\Signature;

class FunctionArgument
{
    /** @var int */
    protected $id;
    /** @var string|null */
    protected $name;
    /** @var string */
    protected $type;

    /**
     * @param int    $id
     * @param string $name
     * @param string $type
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
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setId(?int $id): void
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
    public function setName(?string $name): void
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
        return $this->type;
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

        ksort($tmp);
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