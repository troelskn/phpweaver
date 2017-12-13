<?php

use PHPUnit\Framework\TestCase;

class TestOfClassExtendsScanner extends TestCase
{
    public function testCanTrackSingleExtends()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new TestCallbackListener();
        $inheritance_scanner->notifyOnExtends([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Foo extends Bar {}');
        $token_stream->iterate($scanner);
        $this->assertSame(['Foo', 'Bar'], $listener->one());
    }

    public function testCanTrackSingleImplements()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new TestCallbackListener();
        $inheritance_scanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Foo implements Bar {}');
        $token_stream->iterate($scanner);
        $this->assertSame(['Foo', 'Bar'], $listener->one());
    }

    public function testCanTrackMultipleImplements()
    {
        $scanner = new ScannerMultiplexer();
        $class_scanner = $scanner->appendScanner(new ClassScanner());
        $inheritance_scanner = $scanner->appendScanner(new ClassExtendsScanner($class_scanner));
        $listener = new TestCallbackListener();
        $inheritance_scanner->notifyOnImplements([$listener, 'call']);
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Foo implements Bar, Doink {}');
        $token_stream->iterate($scanner);
        $this->assertSame([['Foo', 'Bar'], ['Foo', 'Doink']], $listener->results());
    }
}
