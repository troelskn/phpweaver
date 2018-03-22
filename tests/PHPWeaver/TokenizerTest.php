<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\TokenStream;
use PHPWeaver\Scanner\TokenStreamParser;
use PHPUnit\Framework\TestCase;

/**
 * todo:
 *   xtrace -> resources ..
 *   use static typehints for parameter types
 *   use docblock comments for parameter types
 *   merge with existing docblock comments.
 */
class TokenizerTest extends TestCase
{
    /**
     * @return void
     */
    public function testTokenizePhpWithoutErrors(): void
    {
        $tokenizer = new TokenStreamParser();
        $tokenStream = $tokenizer->scan('<?php function foo($x) {} ?>');
        $this->assertInstanceOf(TokenStream::class, $tokenStream);
    }
}
