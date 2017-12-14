#!/bin/sh
../trace.sh controller.php
../bin/PHPTracerWeaver Foo.php > Foo.out.php
diff Foo.php Foo.out.php
