<?php

declare(strict_types=1);

namespace Keboola\Gelf\StreamServer;

use Evenement\EventEmitter;
use Keboola\Gelf\Exception\InitException;
use React\Datagram\Factory;
use React\Datagram\Socket;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ServerInterface;

class UdpStreamServer extends EventEmitter implements ServerInterface
{
    /**
     * UDP Socket server
     */
    private Socket $socket;

    /**
     * Event Loop.
     */
    private LoopInterface $loop;

    /**
     * Server address
     */
    private string $address;

    /**
     * TcpStreamServer constructor.
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Start the server by listening on a specified port and address.
     * @param int $port
     * @param string $host
     * @throws InitException
     */
    public function listen(int $port, string $host = '0.0.0.0'): void
    {
        $this->address = $port . ':' . $host;
        $factory = new Factory($this->loop);
        $factory->createServer($host . ':' . $port)->then(function (Socket $server) {
            $this->socket = $server;
            $server->on('message', function ($message) {
                $this->emit('data', [$message]);
            });
        });
    }

    public function getPort(): int
    {
        $name = (string) stream_socket_get_name($this->socket->getLocalAddress(), false);
        return (int) substr((string) strrchr($name, ':'), 1);
    }

    public function close(): void
    {
        $this->socket->end();
        $this->removeAllListeners();
    }

    /**
     * @param resource $socket
     */
    public function createConnection($socket): Connection
    {
        return new Connection($socket, $this->loop);
    }

    /**
     * @inheritdoc
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @inheritdoc
     */
    public function pause(): void
    {
    }

    /**
     * @inheritdoc
     */
    public function resume(): void
    {
    }
}
