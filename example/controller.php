<?php

require_once __DIR__ . '/Foo.php';
require_once __DIR__ . '/Bar.php';

$obj = new Example\Foo();
echo $obj->method1(new Example\Bar());
