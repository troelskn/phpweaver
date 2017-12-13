<?php

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfFunctionBodyScanner extends TestCase
{
    public function testCanTrackFunctionBody()
    {
        $this->markTestSkipped('Partial php is no longer parsable.');
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() {');
        $this->assertFalse($scanner->isActive());
        $tokenStream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }

    public function testCanTrackEndOfFunctionBody()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() { if (true) {} }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    public function testCanTrackFunctionName()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() { print 42; }');
        $tokenStream->iterate($scanner);
        $this->assertSame('bar', $scanner->getName());
    }

    public function testCanTrackEndOfScopedFunctionBody()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Fizz { function buzz() { if (true) {} } }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}
