<?php namespace PHPWeaver\Test;

use PHPWeaver\Scanner\ScannerInterface;
use PHPWeaver\Scanner\Token;
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
        $tokenStream->iterate(new class implements ScannerInterface {
            /** @var int */
            private $count = 0;
            public function __destruct () {
                TokenizerTest::assertSame(12, $this->count);
            }
            public function accept(Token $token): void
            {
                $this->count++;
            }
        });
    }
}
