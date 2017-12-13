<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\Token;

/** Just a dummy really */
class PassthruTransformer implements TransformerInterface
{
    protected $output = '';

    public function accept(Token $token)
    {
        $this->output .= $token->getText();
    }

    public function getOutput()
    {
        return $this->output;
    }
}
