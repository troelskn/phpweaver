<?php
class Signatures {
  protected $signatures_array = array();
  protected $collator;
  function __construct(ClassCollator $collator) {
    $this->collator = $collator;
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
      $this->signatures_array[$name] = new FunctionSignature($this->collator);
    }
    return $this->signatures_array[$name];
  }
  function export() {
    $out = array();
    foreach ($this->signatures_array as $name => $function_signature) {
      $out[$name] = $function_signature->export();
    }
    return $out;
  }
}

class FunctionSignature {
  protected $arguments = array();
  protected $return_type;
  protected $collator;
  function __construct(ClassCollator $collator) {
    $this->collator = $collator;
  }
  function blend($arguments, $return_type) {
    if ($arguments) {
      foreach ($arguments as $id => $type) {
        $arg = $this->getArgumentById($id);
        $arg->collateWith($type);
        if (!$arg->getName()) {
          $arg->setName($id);
        }
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
      $this->arguments[$id] = new FunctionArgument($id, null, '???', $this->collator);
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
  function export() {
    $out = array();
    foreach ($this->arguments as $argument) {
      $out[] = $argument->export();
    }
    return $out;
  }
}

class FunctionArgument {
  protected $id;
  protected $name;
  protected $type;
  protected $collator;
  function __construct($id, $name = null, $type = '???', ClassCollator $collator) {
    $this->id = $id;
    $this->name = $name;
    if ($type === 'null') {
      $this->type = '???';
    } else {
      $this->type = $type;
    }
    $this->collator = $collator;
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
  function collateWith($type) {
    static $primitive = array('boolean', 'string', 'array', 'integer', 'double', 'mixed');
    if ($this->type === $type) {
      return;
    }
    if ($type === 'null') {
      // todo: set this->nullable = true
      return;
    }
    if ($this->type === '???') {
      $this->type = $type;
    } elseif ($type != '???') {
      if (in_array($type, $primitive) || in_array($this->type, $primitive)) {
        $tmp = array($this->type, $type);
        sort($tmp);
        switch (implode(":", $tmp)) {
        case 'integer:string':
        case 'double:string':
          $this->type = 'string';
          break;
        case 'double:integer':
          $this->type = 'double';
          break;
        default:
          $this->type = 'mixed';
        }
      } else {
        //$this->type = $type;
        $collate = $this->collator->collate($this->type, $type);
        $this->type = $collate === '*CANT_COLLATE*' ? 'mixed' : $collate;
      }
    }
  }
  function export() {
    return $this->getName() . ' (' . ($this->isUndefined() ? 'mixed' : $this->getType()) . ')';
  }
}
