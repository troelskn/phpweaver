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
    protected $collate_cache = [];
    protected $ancestors_cache = [];

    public function __construct()
    {
        $this->scanner = new ScannerMultiplexer();
        $class_scanner = $this->scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $this->scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $inheritance_scanner->notifyOnExtends([$this, 'logSupertype']);
        $inheritance_scanner->notifyOnImplements([$this, 'logSupertype']);
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

    public function scanString($php_source)
    {
        $this->collate_cache = [];
        $this->ancestors_cache = [];
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan($php_source);
        $token_stream->iterate($this->scanner);
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

        return $this->symbolsToNames(isset($this->typemap[$class]) ? array_merge([$class], $this->typemap[$class]) : [$class]);
    }

    public function allAncestors($class)
    {
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
        $id = "$first:$second";
        if (!array_key_exists($id, $this->collate_cache)) {
            $intersection = array_intersect($this->allAncestorsAndSelf($first), $this->allAncestorsAndSelf($second));
            $this->collate_cache[$id] = count($intersection) > 0 ? array_shift($intersection) : '*CANT_COLLATE*';
        }

        return $this->collate_cache[$id];
    }
}
