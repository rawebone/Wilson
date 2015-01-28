<?php

namespace Wilson\Tests\Fixtures;

use Wilson\Services;

class TestContainer extends Services
{
    protected $i = 0;

    protected function getService()
    {
        return new \stdClass();
    }

    protected function getValid()
    {
        return isset($this->request) && isset($this->response);
    }

    protected function getStatic()
    {
        return ++$this->i;
    }
}