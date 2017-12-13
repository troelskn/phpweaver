<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class TestOfFunctionParametersScanner extends TestCase
{
    public function testCanTrackCurrentSignatureForFunction()
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar($x) {}');
        $tokenStream->iterate($scanner);
        $this->assertSame('($x)', $scanner->getCurrentSignatureAsString());
    }

    public function testScannerIsActiveAfterFirstOpeningParen()
    {
        $this->markTestSkipped('Partial php is no longer parsable.');
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar(');
        $this->assertFalse($scanner->isActive());
        $tokenStream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }
}
