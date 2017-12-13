#!/bin/bash
../trace.sh controller.php
../bin/PHPTracerWeaver include.php > include.out.php
diff include.php include.out.php
