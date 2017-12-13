<?php namespace PHPTracerWeaver\Signature;

class FunctionArgument
{
    protected $id;
    protected $name;
    /** @var string */
    protected $type;

    public function __construct($id, $name = null, $type = '???')
    {
        if ('null' === $type) {
            $type = '???';
        }

        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function isUndefined()
    {
        return '???' === $this->type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
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
