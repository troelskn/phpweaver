<?php

use PHPUnit\Framework\TestCase;

class TestOfFunctionParametersScanner extends TestCase
{
    public function testCanTrackCurrentSignatureForFunction()
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php function bar($x) {}');
        $token_stream->iterate($scanner);
        $this->assertSame('($x)', $scanner->getCurrentSignatureAsString());
    }

    public function testScannerIsActiveAfterFirstOpeningParen()
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $token_stream = $tokenizer->scan('<?php function bar() {}');
        $this->assertFalse($scanner->isActive());
        $token_stream->iterate($scanner);
        $this->assertTrue($scanner->isActive());
    }
}
