<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\FunctionParametersScanner;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

class FunctionParametersScannerTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanTrackCurrentSignatureForFunction(): void
    {
        $scanner = new FunctionParametersScanner();
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function bar($x) {}');
        $tokenStream->iterate($scanner);
        $this->assertSame('($x)', $scanner->getCurrentSignatureAsString());
    }
}
