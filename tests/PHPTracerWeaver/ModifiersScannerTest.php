<?php

use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfModifiersScanner extends TestCase
{
    public function testCanTrackModifiers()
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { final static protected function foo() {} }');
        $this->assertFalse($scanner->isActive());
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }

    public function testEndsOnFunction()
    {
        $scanner = new ModifiersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php class Foo { final static protected function foo() {} }');
        $tokenStream->iterate($scanner);
        $this->assertFalse($scanner->isActive());
    }
}
