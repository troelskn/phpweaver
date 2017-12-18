<?php namespace PHPWeaver\Reflector;

interface ClassCollatorInterface
{
    /**
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    public function collate(string $first, string $second): string;
}
