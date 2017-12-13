<?php

use PHPTracerWeaver\Scanner\TokenStream;
use PHPTracerWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

/**
 * todo:
 *   xtrace -> resources ..
 *   use static typehints for parameter types
 *   use docblock comments for parameter types
 *   merge with existing docblock comments.
 */
class TestOfTokenizer extends TestCase
{
    public function testTokenizePhpWithoutErrors()
    {
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function foo($x) {} ?>');
        $this->assertInstanceOf(TokenStream::class, $tokenStream);
    }
}
