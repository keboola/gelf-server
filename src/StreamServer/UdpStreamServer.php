<?php

namespace Keboola\Gelf\StreamServer;

use Evenement\EventEmitter;
use React\Datagram\Factory;
use React\Datagram\Socket;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ConnectionException;
use React\Socket\ServerInterface;

class UdpStreamServer extends EventEmitter implements ServerInterface
{
    /**
     * UDP Socket server
     * @var Socket
     */
    private $socket;

    /**
     * Event Loop.
     * @var LoopInterface
     */
    private $loop;

    /**
     * TcpStreamServer constructor.
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Start the server by listening on a specified port and address.
     * @param int $port
     * @param string $host
     * @throws ConnectionException
     */
    public function listen($port, $host = '0.0.0.0')
    {
        $factory = new Factory($this->loop);
        $factory->createServer($host . ':' . $port)->then(function (Socket $server) {
            $this->socket = $server;
            $server->on('message', function ($message) {
                $this->emit('data', [$message]);
            });
        });
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->socket->getLocalAddress(), false);
        return (int) substr(strrchr($name, ':'), 1);
    }

    public function shutdown()
    {
        $this->socket->end();
        $this->removeAllListeners();
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }
}
