<?php
class Signatures {
  protected $signatures_array = array();
  function __construct($signatures_array = array()) {
    foreach ($signatures_array as $name => $sig) {
      $this->signatures_array[strtolower($name)] = $sig;
    }
  }
  function has($func, $class = "") {
    $name = strtolower($class ? ($class . '->' . $func) : $func);
    return isset($this->signatures_array[$name]);
  }
  function get($func, $class = "") {
    if (!$func) {
      throw new Exception("Illegal identifier: {"."$func, $class"."}");
    }
    $name = strtolower($class ? ($class . '->' . $func) : $func);
    if (!isset($this->signatures_array[$name])) {
      $this->signatures_array[$name] = new FunctionSignature();
    }
    return $this->signatures_array[$name];
  }
}

/**
 * Signatures are collected at 3 different places (in order of authority):
 *   static analysis (typehints)
 *   runtime analysis (trace)
 *   docblockcomments (optional)
 */
class FunctionSignature {
  protected $arguments = array();
  protected $return_type;
  function blend($arguments, $return_type) {
    if ($arguments) {
      foreach ($arguments as $id => $type) {
        $arg = $this->getArgumentById($id);
        $arg->blendType($type);
      }
    }
    if ($return_type) {
      $this->return_type = $return_type;
    }
  }
  function getReturnType() {
    return $this->return_type;
  }
  function getArgumentById($id) {
    if (!isset($this->arguments[$id])) {
      $this->arguments[$id] = new FunctionArgument($id);
    }
    return $this->arguments[$id];
  }
  function getArgumentByName($name) {
    foreach ($this->arguments as $argument) {
      if ($argument->getName() === $name) {
        return $argument;
      }
    }
  }
  function getArguments() {
    $args = $this->arguments;
    ksort($args);
    return $args;
  }
}

class FunctionArgument {
  protected $id;
  protected $name;
  protected $type;
  function __construct($id, $name = null, $type = '???') {
    $this->id = $id;
    $this->name = $name;
    $this->type = $type;
  }
  function getId() {
    return $this->id;
  }
  function setId($id) {
    $this->id = $id;
  }
  function getName() {
    return $this->name;
  }
  function setName($name) {
    $this->name = $name;
  }
  function isUndefined() {
    return $this->type === '???';
  }
  function getType() {
    return $this->type;
  }
  function setType($type) {
    $this->type = $type;
  }
  function blendType($type) {
    // todo: could probably be more intelligent
    if ($type != '???') {
      $this->type = $type;
    }
  }
}
