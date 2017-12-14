<?php namespace PHPTracerWeaver\Test;

use PHPTracerWeaver\Exceptions\Exception;

class CallbackListener
{
    /** @var array[] */
    protected $results = [];

    /**
     * @return array[]
     */
    public function results(): array
    {
        return $this->results;
    }

    /**
     * @return mixed[]
     */
    public function one()
    {
        if (1 !== count($this->results)) {
            throw new Exception('Expected exactly one result');
        }

        return $this->results[0];
    }

    /**
     * @param mixed... $params
     *
     * @return void
     */
    public function call(...$params): void
    {
        $this->results[] = $params;
    }
}
