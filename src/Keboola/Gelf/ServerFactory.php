<?php

namespace Keboola\Gelf;

use Keboola\Gelf\Socket\UDPSocket;

class ServerFactory
{
    public function getUDPServer($ipAddress = null, $port = null)
    {
        $socket = new UDPSocket($ipAddress, $port);
        return new Server($socket);
    }
    
    public function getTCPServer($ipAddress = null, $port = null)
    {
        
    }
    
    public function getHTTPServer($ipAddress = null, $port = null)
    {
        
    }
    
}