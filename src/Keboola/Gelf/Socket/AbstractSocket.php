<?php

namespace Keboola\Gelf\Socket;

class AbstractSocket
{
    /**
     * @var int
     */
    protected $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * Return actual connected port.
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    public function getResource()
    {
        return $this->socket;
    }
}
