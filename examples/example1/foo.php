<?php

class Foo
{
    public function dims($param1, $param2 = null)
    {
        return 42;
    }
}

class Bar
{
}

$f = new Foo();
$f->dims(new Bar());
