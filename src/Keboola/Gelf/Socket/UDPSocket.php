<?php

namespace Keboola\Gelf\Socket;

use Keboola\Gelf\Exception\InitException;

class UDPSocket extends AbstractSocket
{
    const DEFAULT_GELF_UDP_PORT = 12201;

    /**
     * UDPSocket constructor.
     * @param string $ipAddress IP address to bind to, use null to bind to all addresses.
     * @param integer $port Port to bind to, use null for default.
     */
    public function __construct($ipAddress = null, $port = null)
    {
        if ($ipAddress === null) {
            $ipAddress = '0.0.0.0';
        }
        if ($port === null) {
            $this->port = self::DEFAULT_GELF_UDP_PORT;
        } else {
            $this->port = $port;
        }

        //Create a UDP socket
        if (!($this->socket = socket_create(AF_INET, SOCK_DGRAM, 0))) {
            $errorCode = socket_last_error();
            $errorMsg = socket_strerror($errorCode);
            throw new InitException("Cannot create UDP socket: $errorMsg [$errorCode]");
        }

        // Bind the source address
        if (!socket_bind($this->socket, $ipAddress, $this->port)) {
            $errorCode = socket_last_error();
            $errorMsg = socket_strerror($errorCode);
            throw new InitException("Cannot listen on UDP socket on : $errorMsg [$errorCode]");
        }
    }
}
