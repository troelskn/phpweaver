<?php namespace PHPTracerWeaver\Reflector;

class DummyClassCollator implements ClassCollatorInterface
{
    public function collate($first, $second)
    {
        return 'mixed';
    }
}
