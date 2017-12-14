<?php

require_once __DIR__ . '/Foo.php';
require_once __DIR__ . '/Bar.php';

$f = new Foo();
$f->dims(new Bar());
