<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\ClassExtendsScanner;
use PHPWeaver\Scanner\ClassScanner;
use PHPWeaver\Scanner\ScannerMultiplexer;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class ClassExtendsScannerTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackSingleExtends(): void
    {
        $scanner = new ScannerMultiplexer();
        $classScanner = new ClassScanner();
        $scanner->appendScanner($classScanner);
        $inheritanceScanner = new ClassExtendsScanner($classScanner);
        $scanner->appendScanner($inheritanceScanner);
        $listener = new CallbackListener();
        $inheritanceScanner->notifyOnExtends([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo extends Bar {}');
        $tokenStream->iterate($scanner);
        $this->assertSame(['Foo', 'Bar'], $listener->one());
    }

    /**
     * @return void
     */
    public function testCanTrackSingleImplements(): void
    {
        $scanner = new ScannerMultiplexer();
        $classScanner = new ClassScanner();
        $scanner->appendScanner($classScanner);
        $inheritanceScanner = new ClassExtendsScanner($classScanner);
        $scanner->appendScanner($inheritanceScanner);
        $listener = new CallbackListener();
        $inheritanceScanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo implements Bar {}');
        $tokenStream->iterate($scanner);
        $this->assertSame(['Foo', 'Bar'], $listener->one());
    }

    /**
     * @return void
     */
    public function testCanTrackMultipleImplements(): void
    {
        $scanner = new ScannerMultiplexer();
        $classScanner = new ClassScanner();
        $scanner->appendScanner($classScanner);
        $inheritanceScanner = new ClassExtendsScanner($classScanner);
        $scanner->appendScanner($inheritanceScanner);
        $listener = new CallbackListener();
        $inheritanceScanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo implements Bar, Doink {}');
        $tokenStream->iterate($scanner);
        $this->assertSame([['Foo', 'Bar'], ['Foo', 'Doink']], $listener->results());
    }
}
