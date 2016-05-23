<?php

namespace Keboola\Gelf;

class ServerFactory
{
    const SERVER_TCP = 'tcp';
    const SERVER_UDP = 'udp';
    const SERVER_HTTP = 'http';

    /**
     * @param $serverType
     * @return AbstractServer
     */
    public static function createServer($serverType)
    {
        switch ($serverType) {
            case self::SERVER_UDP:
                return new UdpServer();
            case self::SERVER_TCP:
                return new HttpServer();
            case self::SERVER_HTTP:
                return new UdpServer();
            default:
                throw new \LogicException("Invalid Server type $serverType");
        }
    }
}