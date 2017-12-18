<?php namespace PHPWeaver\Reflector;

use PHPWeaver\Scanner\ClassExtendsScanner;
use PHPWeaver\Scanner\ClassScanner;
use PHPWeaver\Scanner\NamespaceScanner;
use PHPWeaver\Scanner\ScannerMultiplexer;
use PHPWeaver\Scanner\TokenStreamParser;

class StaticReflector implements ClassCollatorInterface
{
    /** @var ScannerMultiplexer */
    protected $scanner;
    /** @var string[] */
    protected $names = [];
    /** @var array[] */
    protected $typemap = [];
    /** @var string[] */
    protected $collateCache = [];
    /** @var array[] */
    protected $ancestorsCache = [];

    public function __construct()
    {
        $this->scanner = new ScannerMultiplexer();
        $namespaceScanner = new NamespaceScanner();
        $classScanner = new ClassScanner();
        $inheritanceScanner = new ClassExtendsScanner($classScanner);
        $this->scanner->appendScanner($namespaceScanner);
        $this->scanner->appendScanner($classScanner);
        $this->scanner->appendScanner($inheritanceScanner);
        $inheritanceScanner->notifyOnExtends([$this, 'logSupertype']);
        $inheritanceScanner->notifyOnImplements([$this, 'logSupertype']);
    }

    /**
     * @param string $class
     * @param string $super
     *
     * @return void
     */
    public function logSupertype(string $class, string $super): void
    {
        $this->names[strtolower($super)] = $super;
        $this->names[strtolower($class)] = $class;
        $class = strtolower($class);
        $super = strtolower($super);
        if (!isset($this->typemap[$class])) {
            $this->typemap[$class] = [];
        }
        if (!in_array($super, $this->typemap[$class], true)) {
            $this->typemap[$class][] = $super;
        }
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public function scanFile(string $file): void
    {
        $this->scanString(file_get_contents($file) ?: '');
    }

    /**
     * @param string $phpSource
     *
     * @return void
     */
    public function scanString(string $phpSource): void
    {
        $this->collateCache = [];
        $this->ancestorsCache = [];
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan($phpSource);
        $tokenStream->iterate($this->scanner);
    }

    /**
     * @return array[]
     */
    public function export(): array
    {
        return $this->typemap;
    }

    /**
     * @param string[] $symbols
     *
     * @return string[]
     */
    protected function symbolsToNames(array $symbols = []): array
    {
        $names = [];
        foreach ($symbols as $symbol) {
            $names[] = $this->names[$symbol];
        }

        return $names;
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function ancestors(string $class): array
    {
        $class = strtolower($class);

        return $this->symbolsToNames(isset($this->typemap[$class]) ? $this->typemap[$class] : []);
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function ancestorsAndSelf(string $class): array
    {
        $class = strtolower($class);

        $symbols = isset($this->typemap[$class]) ? array_merge([$class], $this->typemap[$class]) : [$class];

        return $this->symbolsToNames($symbols);
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function allAncestors(string $class): array
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

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function allAncestorsAndSelf(string $class): array
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
    public function collate(string $first, string $second): string
    {
        $first = strtolower($first);
        $second = strtolower($second);
        $id = $first . '|' . $second;
        if (!array_key_exists($id, $this->collateCache)) {
            $intersection = array_intersect($this->allAncestorsAndSelf($first), $this->allAncestorsAndSelf($second));
            $this->collateCache[$id] = count($intersection) > 0 ? array_shift($intersection) : '*CANT_COLLATE*';
        }

        return $this->collateCache[$id];
    }
}
