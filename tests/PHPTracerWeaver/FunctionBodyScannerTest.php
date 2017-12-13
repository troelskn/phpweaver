<?php

use PHPUnit\Framework\TestCase;

class TestOfFunctionBodyScanner extends TestCase
{
    public function testCanTrackFunctionBody()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php function bar() {');
        $this->assertFalse($scanner->isActive());
        $token_stream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }

    public function testCanTrackEndOfFunctionBody()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php function bar() { if (true) {} }');
        $token_stream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    public function testCanTrackFunctionName()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php function bar() { print 42;');
        $token_stream->iterate($scanner);
        $this->assertSame('bar', $scanner->getName());
    }

    public function testCanTrackEndOfScopedFunctionBody()
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php class Fizz { function buzz() { if (true) {} }');
        $token_stream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}
