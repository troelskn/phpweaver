<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class FunctionBodyScannerTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackFunctionBody(): void
    {
        $this->markTestSkipped('Partial php is no longer parsable.');
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() {');
        $this->assertFalse($scanner->isActive());
        $tokenStream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }

    /**
     * @return void
     */
    public function testCanTrackEndOfFunctionBody(): void
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() { if (true) {} }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    /**
     * @return void
     */
    public function testCanTrackFunctionName(): void
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() { print 42; }');
        $tokenStream->iterate($scanner);
        $this->assertSame('bar', $scanner->getName());
    }

    /**
     * @return void
     */
    public function testCanTrackEndOfScopedFunctionBody(): void
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Fizz { function buzz() { if (true) {} } }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}
