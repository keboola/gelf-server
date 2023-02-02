<?php

declare(strict_types=1);

namespace Keboola\Gelf\Exception;

use RuntimeException;

class InvalidMessageException extends RuntimeException
{
    protected string $data;

    public function __construct(string $message, string $data)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
