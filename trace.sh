#!/usr/bin/env sh
print_usage() {
    echo "Traces function signatures in a running PHP script."
    printf "Usage: %s [OPTIONS] PHPSCRIPT\n" $(basename $0)
    echo "  PHPSCRIPT   A PHP file to analyse"
    echo "  OPTIONS     The PHP script is launched with these options, verbose"
    echo "Output is written to dumpfile.xt"
    exit 1
}

if [ $# -eq 0 ]
then
    print_usage
fi;

if [ -f dumpfile.xt ]
then
    rm dumpfile.xt
fi

echo Running script with instrumentation: $@
php -d xdebug.auto_trace=1 -d xdebug.trace_options=1 -d xdebug.trace_output_dir=$(pwd) -d xdebug.trace_output_name=dumpfile -d xdebug.trace_format=0 -d xdebug.collect_params=1 -d xdebug.collect_return=1 "$@"
echo "TRACE COMPLETE"
