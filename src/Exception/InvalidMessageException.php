<?php

namespace Keboola\Gelf\Exception;

class InvalidMessageException extends \RuntimeException
{
    protected $data;

    public function __construct($message, $data)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
