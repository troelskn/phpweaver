<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\ClassScanner;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class ClassScannerTest extends TestCase
{
    /**
     * @return void
     */
    public function testForgetsClassWhenScopeEnds(): void
    {
        $scanner = new ClassScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { function bar($x) {} }');
        $tokenStream->iterate($scanner);
        $this->assertSame('', $scanner->getCurrentClass());
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
        $this->assertSame('', $scanner->getCurrentClass());
    }
}
