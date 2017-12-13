#!/bin/bash
ls -alp --color
../../trace.sh ../../vendor/bin/phpunit -c ../../phpunit.xml
ls -alp --color
../../bin/PHPTracerWeaver ../../src/PHPTracerWeaver/transform.inc.php > transform.inc.out.php
meld ../../src/PHPTracerWeaver/transform.inc.php transform.inc.out.php

