#!/bin/bash
ls -alp --color
../../trace.sh ../../test.php
ls -alp --color
../../weave.php ../../transform.inc.php > transform.inc.out.php
meld ../../transform.inc.php transform.inc.out.php

