#!/bin/sh
../bin/phpweaver trace controller.php
../bin/phpweaver weave ./Foo.php
cat Foo.php
