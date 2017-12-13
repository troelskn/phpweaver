<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfModifiersScanner extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackModifiers(): void
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { final static protected function foo() {} }');
        $this->assertFalse($scanner->isActive());
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    /**
     * @return void
     */
    public function testEndsOnFunction(): void
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { final static protected function foo() {} }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}
