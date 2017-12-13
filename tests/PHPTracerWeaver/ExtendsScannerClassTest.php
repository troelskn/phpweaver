<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\ClassExtendsScanner;
use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\ScannerMultiplexer;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfClassExtendsScanner extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackSingleExtends(): void
    {
        $scanner = new ScannerMultiplexer();
        $classScanner = $scanner->appendScanner(new ClassScanner());
        $inheritanceScanner = $scanner->appendScanner(new ClassExtendsScanner($classScanner));
        $listener = new TestCallbackListener();
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
        $classScanner = $scanner->appendScanner(new ClassScanner());
        $inheritanceScanner = $scanner->appendScanner(new ClassExtendsScanner($classScanner));
        $listener = new TestCallbackListener();
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
        $classScanner = $scanner->appendScanner(new ClassScanner());
        $inheritanceScanner = $scanner->appendScanner(new ClassExtendsScanner($classScanner));
        $listener = new TestCallbackListener();
        $inheritanceScanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo implements Bar, Doink {}');
        $tokenStream->iterate($scanner);
        $this->assertSame([['Foo', 'Bar'], ['Foo', 'Doink']], $listener->results());
    }
}
