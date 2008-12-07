#!/usr/bin/php
<?php
require_once 'signature.inc.php';
require_once 'xtrace.inc.php';
require_once 'scanner.inc.php';
require_once 'transform.inc.php';
require_once 'reflector.inc.php';

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
  error_reporting(E_ALL | E_STRICT);

  $trace_filename = "dumpfile.xt";
  $file_to_weave = $argv[1];
  if (!is_file($file_to_weave)) {
    throw new Exception("File ($file_to_weave) isn't readable");
  }

  // read trace
  $reflector = new StaticReflector();
  $sigs = new Signatures();
  $trace = new xtrace_TraceReader(new SplFileObject($trace_filename));
  $collector = new xtrace_TraceSignatureLogger($sigs, $reflector);
  $trace->process(new xtrace_FunctionTracer($collector, $reflector));

  // transform file
  $scanner = new ScannerMultiplexer();
  $parameters_scanner = $scanner->appendScanner(new FunctionParametersScanner());
  $function_body_scanner = $scanner->appendScanner(new FunctionBodyScanner());
  $modifiers_scanner = $scanner->appendScanner(new ModifiersScanner());
  $class_scanner = $scanner->appendScanner(new ClassScanner());
  $editor = new TracerDocBlockEditor($sigs, $class_scanner, $function_body_scanner);
  $transformer = $scanner->appendScanner(new DocCommentEditorTransformer($function_body_scanner, $modifiers_scanner, $parameters_scanner, $editor));
  $tokenizer = new TokenStreamParser();
  $token_stream = $tokenizer->scan(file_get_contents($file_to_weave));
  $token_stream->iterate($scanner);
  echo $transformer->getOutput();

}