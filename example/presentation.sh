#!/bin/sh
../bin/trace.sh controller.php
../bin/php-tracer-weaver Foo.php > Foo.out.php
diff Foo.php Foo.out.php
