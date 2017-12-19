<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\Token;

/** Just a dummy really */
class PassthruTransformer implements TransformerInterface
{
    /** @var string */
    protected $output = '';

    /**
     * @param Token $token
     *
     * @return void
     */
    public function accept(Token $token): void
    {
        $this->output .= $token->getText();
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }
}
