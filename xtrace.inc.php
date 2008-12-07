<?php
  /**
   * Class for parsing xdebug function trace files
   */
class xtrace_TraceReader {
  protected $file;
  function __construct($file) {
    $this->file = $file;
  }
  function process($handler) {
    foreach ($this->file as $line) {
      if (preg_match('~TRACE START \\[([0-9 :-]+)\\]~', $line, $mm)) {
        $handler->trace_start($mm[1]);
      } elseif (preg_match('~TRACE END   \\[([0-9 :-]+)\\]~', $line, $mm)) {
        $handler->trace_end($mm[1]);
      } elseif (preg_match('~^\\s+([0-9.]+)\\s+([0-9.]+)\\s+-> ([^(]+)\\((.*)\\)\\s+([^:]+):([0-9]+)$~', $line, $mm)) { // runtime-generated functions?
        $handler->fun_call(array(
          'time' => $mm[1],
          'memory_usage' => $mm[2],
          'function' => $mm[3],
          'arguments' => $mm[4],
          'filename' => $mm[5],
          'linenumber' => $mm[6]));
      } elseif (preg_match('~^\\s+>=> (.+)$~', $line, $mm)) {
        $handler->return_value($mm[1]);
      } elseif (preg_match('~^\\s+[0-9.]+\\s+[0-9.]+$~', $line)) {
        // dunno what this is?
      } elseif (preg_match('~^\\s*$~', $line)) {
      } else {
        $handler->miss($line);
      }
    }
  }
}

class xtrace_FunctionTracer {
  protected $handler;
  protected $stack = array();
  protected $internal_functions;
  function __construct($handler) {
    $this->handler = $handler;
    $defined_functions = get_defined_functions();
    $this->internal_functions = array_merge($defined_functions['internal'], array('include', 'include_once', 'require', 'require_once'));
  }
  function trace_start($time) {}
  function trace_end($time) {}
  function miss($line) {
    echo "miss($line)\n";
    die;
  }
  function fun_call($trace) {
    $this->stack[] = $trace;
  }
  function return_value($value) {
    $fun_call = array_pop($this->stack);
    if (!isset($fun_call['function'])) {
      echo "xtrace_FunctionTracer failure in return_value()\n";
      var_dump($this->stack);
      var_dump($fun_call);
      var_dump($value);
      exit;
    }
    $fun_call['return_value'] = $value;
    if (!in_array($fun_call['function'], $this->internal_functions)) {
      $this->handler->log($fun_call);
    }
  }
}

class xtrace_TraceSignatureLogger {
  protected $signatures;
  protected $reflector;
  protected $includes = array();
  function __construct(Signatures $signatures, StaticReflector $reflector = null) {
    $this->signatures = $signatures;
    $this->reflector = $reflector;
  }
  function log($trace) {
    if ($this->reflector) {
      $filename = isset($trace['filename']) ? $trace['filename'] : '';
      if (!isset($this->includes[$filename]) && is_file($filename)) {
        $this->reflector->scanFile($filename);
      }
      $this->includes[$filename] = true;
    }
    $sig = $this->signatures->get($trace['function']);
    $sig->blend(
      $this->parseArguments($trace['arguments']),
      $this->parseReturnType($trace['return_value']));
  }
  function parseArguments($as_string) {
    // todo: resources ..
    $types = array();
    foreach (explode(", ", $as_string) as $type) {
      if ($type) {
        if (preg_match('~^string\\([0-9]+\\)$~', $type)) {
          $types[] = "string";
        } elseif (preg_match('~^array\\([0-9]+\\)$~', $type)) {
          $types[] = "array";
        } elseif (preg_match('~^class (.+)$~', $type, $mm)) {
          $types[] = $mm[1];
        } else {
          $types[] = $type;
        }
      }
    }
    return $types;
  }
  function parseReturnType($return_value) {
    // todo: numbers, resources ..
    if ($return_value === 'TRUE' || $return_value === 'FALSE') {
      return 'boolean';
    }
    if ($return_value === 'NULL') {
      return 'null';
    }
    if (substr($return_value, 0, 1) === "'") {
      return 'string';
    }
    if (substr($return_value, 0, 5) === "array") {
      return 'array';
    }
    if (preg_match('~^class (\w+)~', $return_value, $mm)) {
      return $mm[1];
    }
    if (preg_match('~^[0-9]+$~', $return_value)) {
      return 'integer';
    }
    return 'mixed';
  }
}
