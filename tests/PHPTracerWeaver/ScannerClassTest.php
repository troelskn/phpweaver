<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\ClassScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfClassScanner extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackCurrentClass(): void
    {
        $this->markTestSkipped('Partial php is no longer parsable.');
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { function bar($x) {}');
        $tokenStream->iterate($scanner);
        $this->assertSame('Foo', $scanner->getCurrentClass());
    }

    /**
     * @return void
     */
    public function testForgetsClassWhenScopeEnds(): void
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { function bar($x) {} }');
        $tokenStream->iterate($scanner);
        $this->assertNull($scanner->getCurrentClass());
    }

    /**
     * @return void
     */
    public function testForgetsClassWhenScopeEndsWithinNestedScopes(): void
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php while (true) { class Foo { function bar($x) {} } }');
        $tokenStream->iterate($scanner);
        $this->assertNull($scanner->getCurrentClass());
    }
}
