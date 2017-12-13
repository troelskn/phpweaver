<?php namespace PHPTracerWeaver\Reflector;

use PHPTracerWeaver\Scanner\ClassExtendsScanner;
use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\TokenStreamParser;

class StaticReflector implements ClassCollatorInterface
{
    /** @var ScannerMultiplexer */
    protected $scanner;
    protected $names = [];
    protected $typemap = [];
    protected $collateCache = [];
    protected $ancestorsCache = [];

    public function __construct()
    {
        $this->scanner = new ScannerMultiplexer();
        $classScanner = $this->scanner->appendScanner(new ClassScanner());
        $inheritanceScanner = $this->scanner->appendScanner(new ClassExtendsScanner($classScanner));
        $inheritanceScanner->notifyOnExtends([$this, 'logSupertype']);
        $inheritanceScanner->notifyOnImplements([$this, 'logSupertype']);
    }

    public function logSupertype($class, $super)
    {
        $this->names[strtolower($super)] = $super;
        $this->names[strtolower($class)] = $class;
        $class = strtolower($class);
        $super = strtolower($super);
        if (!isset($this->typemap[$class])) {
            $this->typemap[$class] = [];
        }
        if (!in_array($super, $this->typemap[$class])) {
            $this->typemap[$class][] = $super;
        }
    }

    public function scanFile($file)
    {
        $this->scanString(file_get_contents($file));
    }

    public function scanString($phpSource)
    {
        $this->collateCache = [];
        $this->ancestorsCache = [];
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan($phpSource);
        $tokenStream->iterate($this->scanner);
    }

    public function export()
    {
        return $this->typemap;
    }

    protected function symbolsToNames($symbols = [])
    {
        $names = [];
        foreach ($symbols as $symbol) {
            $names[] = $this->names[$symbol];
        }

        return $names;
    }

    public function ancestors($class)
    {
        $class = strtolower($class);

        return $this->symbolsToNames(isset($this->typemap[$class]) ? $this->typemap[$class] : []);
    }

    public function ancestorsAndSelf($class)
    {
        $class = strtolower($class);

        $symbols = isset($this->typemap[$class]) ? array_merge([$class], $this->typemap[$class]) : [$class];

        return $this->symbolsToNames($symbols);
    }

    public function allAncestors($class)
    {
        $class = strtolower($class);
        if (isset($this->ancestorsCache[$class])) {
            return $this->ancestorsCache[$class];
        }
        $result = $this->ancestors($class);
        foreach ($result as $p) {
            $result = array_merge($result, $this->allAncestors($p));
        }
        $this->ancestorsCache[$class] = $result;

        return $result;
    }

    public function allAncestorsAndSelf($class)
    {
        if (!isset($this->names[strtolower($class)])) {
            return $this->allAncestors($class);
        }

        return array_merge([$this->names[strtolower($class)]], $this->allAncestors($class));
    }

    /**
     * Finds the first common ancestor, if possible.
     *
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    public function collate($first, $second)
    {
        $first = strtolower($first);
        $second = strtolower($second);
        $id = $first . ':' . $second;
        if (!array_key_exists($id, $this->collateCache)) {
            $intersection = array_intersect($this->allAncestorsAndSelf($first), $this->allAncestorsAndSelf($second));
            $this->collateCache[$id] = count($intersection) > 0 ? array_shift($intersection) : '*CANT_COLLATE*';
        }

        return $this->collateCache[$id];
    }
}
