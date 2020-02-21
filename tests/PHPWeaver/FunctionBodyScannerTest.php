<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\FunctionBodyScanner;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class FunctionBodyScannerTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackEndOfFunctionBody(): void
    {
        $scanner = new FunctionBodyScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar() { if (true) {} }');
        $tokenStream->iterate($scanner);
        static::assertFalse($scanner->isActive());
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
        static::assertSame('bar', $scanner->getName());
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
        static::assertFalse($scanner->isActive());
    }
}
