<?php namespace Example;

class Foo
{
    private $obj;

    public function method1($param1, $param2 = null)
    {
        $this->obj = $param1;

        return $param2 ?: 42;
    }
}
