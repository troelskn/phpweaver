<?php
interface ClassCollator {
  function collate($first, $second);
}

class DummyClassCollator implements ClassCollator {
  function collate($first, $second) {
    return 'mixed';
  }
}

class TraceIncludesLogger {
  protected $reflector;
  protected $includes = array();
  function __construct(StaticReflector $reflector) {
    $this->reflector = $reflector;
  }
  function log($trace) {
    $filename = isset($trace['filename']) ? $trace['filename'] : '';
    if (!isset($this->includes[$filename]) && is_file($filename)) {
      $this->reflector->scanFile($filename);
    }
    $this->includes[$filename] = true;
  }
  function log_include($trace) {
    $this->log($trace);
  }
}

class StaticReflector implements ClassCollator {
  protected $scanner;
  protected $names = array();
  protected $typemap = array();
  protected $collate_cache = array();
  protected $ancestors_cache = array();
  function __construct() {
    $this->scanner = new ScannerMultiplexer();
    $class_scanner = $this->scanner->appendScanner(new ClassScanner());
    $inheritance_scanner = $this->scanner->appendScanner(new ClassExtendsScanner($class_scanner));
    $inheritance_scanner->notifyOnExtends(array($this, 'logSupertype'));
    $inheritance_scanner->notifyOnImplements(array($this, 'logSupertype'));
  }
  function logSupertype($class, $super) {
    $this->names[strtolower($super)] = $super;
    $this->names[strtolower($class)] = $class;
    $class = strtolower($class);
    $super = strtolower($super);
    if (!isset($this->typemap[$class])) {
      $this->typemap[$class] = array();
    }
    if (!in_array($super, $this->typemap[$class])) {
      $this->typemap[$class][] = $super;
    }
  }
  function scanFile($file) {
    $this->scanString(file_get_contents($file));
  }
  function scanString($php_source) {
    $this->collate_cache = array();
    $this->ancestors_cache = array();
    $tokenizer = new TokenStreamParser();
    $token_stream = $tokenizer->scan($php_source);
    $token_stream->iterate($this->scanner);
  }
  function export() {
    return $this->typemap;
  }
  protected function symbolsToNames($symbols = array()) {
    $names = array();
    foreach ($symbols as $symbol) {
      $names[] = $this->names[$symbol];
    }
    return $names;
  }
  function ancestors($class) {
    $class = strtolower($class);
    return $this->symbolsToNames(isset($this->typemap[$class]) ? $this->typemap[$class] : array());
  }
  function ancestorsAndSelf($class) {
    $class = strtolower($class);
    return $this->symbolsToNames(isset($this->typemap[$class]) ? array_merge(array($class), $this->typemap[$class]) : array($class));
  }
  function allAncestors($class) {
    $class = strtolower($class);
    if (isset($this->ancestors_cache[$class])) {
      return $this->ancestors_cache[$class];
    }
    $result = $this->ancestors($class);
    foreach ($result as $p) {
      $result = array_merge($result, $this->allAncestors($p));
    }
    $this->ancestors_cache[$class] = $result;
    return $result;
  }
  function allAncestorsAndSelf($class) {
    return array_merge(array($this->names[strtolower($class)]), $this->allAncestors($class));
  }
  /**
   * Finds the first common ancestor, if possible
   */
  function collate($first, $second) {
    $first = strtolower($first);
    $second = strtolower($second);
    $id = "$first:$second";
    if (!array_key_exists($id, $this->collate_cache)) {
      $intersection = array_intersect($this->allAncestorsAndSelf($first), $this->allAncestorsAndSelf($second));
      $this->collate_cache[$id] = count($intersection) > 0 ? array_shift($intersection) : '*CANT_COLLATE*';
    }
    return $this->collate_cache[$id];
  }
}