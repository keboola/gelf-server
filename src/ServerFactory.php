<?php

declare(strict_types=1);

namespace Keboola\Gelf;

use LogicException;

class ServerFactory
{
    public const SERVER_TCP = 'tcp';
    public const SERVER_UDP = 'udp';
    public const SERVER_HTTP = 'http';

    public static function createServer(string $serverType): AbstractServer
    {
        switch ($serverType) {
            case self::SERVER_UDP:
                return new UdpServer();
            case self::SERVER_TCP:
                return new TcpServer();
            case self::SERVER_HTTP:
                return new HttpServer();
            default:
                throw new LogicException("Invalid Server type $serverType");
        }
    }
}
