<?php namespace PHPWeaver\Reflector;

class DummyClassCollator implements ClassCollatorInterface
{
    /**
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    public function collate(string $first, string $second): string
    {
        return $first . '|' . $second;
    }
}
