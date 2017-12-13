#!/bin/bash
ls -alp --color
source-highlight --out-format=esc --line-number --input include.php && echo
source-highlight --out-format=esc --line-number --input controller.php && echo
../../trace.sh controller.php
../../bin/PHPTracerWeaver include.php > include.out.php
meld include.php include.out.php

