<?php namespace PHPWeaver\Scanner;

/** parses a string -> tokenstream */
class TokenStreamParser
{
    public function scan(string $source): TokenStream
    {
        //todo: track indentation
        $stream = new TokenStream();
        $depth = 0;
        foreach (token_get_all($source, TOKEN_PARSE) as $token) {
            $text = $token;
            $token = -1;
            if (is_array($text)) {
                [$token, $text] = $text;
            }
            if (T_CURLY_OPEN === $token || T_DOLLAR_OPEN_CURLY_BRACES === $token || '{' === $text) {
                ++$depth;
            } elseif ('}' == $text) {
                --$depth;
            }
            $stream->append(new Token($text, $token, $depth));
        }

        return $stream;
    }
}
