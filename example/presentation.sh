#!/bin/sh
../bin/phpweaver trace controller.php
cp Foo.php Foo.out.php
../bin/phpweaver weave Foo.out.php --overwrite
diff Foo.php Foo.out.php
