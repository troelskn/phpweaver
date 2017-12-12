<?php
class Signatures
{
    protected $signatures_array = [];
    protected $collator;

    public function __construct(ClassCollator $collator)
    {
        $this->collator = $collator;
    }

    public function has($func, $class = '')
    {
        $name = strtolower($class ? ($class . '->' . $func) : $func);

        return isset($this->signatures_array[$name]);
    }

    public function get($func, $class = '')
    {
        if (!$func) {
            throw new Exception('Illegal identifier: {' . "$func, $class" . '}');
        }
        $name = strtolower($class ? ($class . '->' . $func) : $func);
        if (!isset($this->signatures_array[$name])) {
            $this->signatures_array[$name] = new FunctionSignature($this->collator);
        }

        return $this->signatures_array[$name];
    }

    public function export()
    {
        $out = [];
        foreach ($this->signatures_array as $name => $function_signature) {
            $out[$name] = $function_signature->export();
        }

        return $out;
    }
}

class FunctionSignature
{
    protected $arguments = [];
    protected $return_type;
    protected $collator;

    public function __construct(ClassCollator $collator)
    {
        $this->collator = $collator;
    }

    public function blend($arguments, $return_type)
    {
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

    public function getReturnType()
    {
        return $this->return_type;
    }

    public function getArgumentById($id)
    {
        if (!isset($this->arguments[$id])) {
            $this->arguments[$id] = new FunctionArgument($id, null, '???', $this->collator);
        }

        return $this->arguments[$id];
    }

    public function getArgumentByName($name)
    {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument;
            }
        }
    }

    public function getArguments()
    {
        $args = $this->arguments;
        ksort($args);

        return $args;
    }

    public function export()
    {
        $out = [];
        foreach ($this->arguments as $argument) {
            $out[] = $argument->export();
        }

        return $out;
    }
}

class FunctionArgument
{
    protected $id;
    protected $name;
    protected $type;
    protected $collator;

    public function __construct($id, $name = null, $type = '???', ClassCollator $collator)
    {
        $this->id = $id;
        $this->name = $name;
        if ('null' === $type) {
            $this->type = '???';
        } else {
            $this->type = $type;
        }
        $this->collator = $collator;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function isUndefined()
    {
        return '???' === $this->type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function collateWith($type)
    {
        static $primitive = ['boolean', 'string', 'array', 'integer', 'double', 'mixed'];
        if ($this->type === $type) {
            return;
        }
        if ('null' === $type) {
            // todo: set this->nullable = true
            return;
        }
        if ('???' === $this->type) {
            $this->type = $type;
        } elseif ('???' != $type) {
            if (in_array($type, $primitive) || in_array($this->type, $primitive)) {
                $tmp = [$this->type, $type];
                sort($tmp);
                switch (implode(':', $tmp)) {
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
                $this->type = '*CANT_COLLATE*' === $collate ? 'mixed' : $collate;
            }
        }
    }

    public function export()
    {
        return $this->getName() . ' (' . ($this->isUndefined() ? 'mixed' : $this->getType()) . ')';
    }
}
