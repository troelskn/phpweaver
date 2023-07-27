<?php namespace PHPWeaver\Signature;

class FunctionArgument
{
    /** @var array<string, true> */
    protected $types = [];

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

    /**
     * @param array<string, true> $types
     * @return array<string, true>
     */
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
}
