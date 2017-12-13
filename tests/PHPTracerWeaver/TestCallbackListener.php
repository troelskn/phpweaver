<?php namespace PHPTracerWeaver\Test;

class TestCallbackListener
{
    protected $results = [];

    public function results()
    {
        return $this->results;
    }

    public function one()
    {
        if (1 !== count($this->results)) {
            throw new Exception('Expected exactly one result');
        }

        return $this->results[0];
    }

    public function call()
    {
        $this->results[] = func_get_args();
    }
}
