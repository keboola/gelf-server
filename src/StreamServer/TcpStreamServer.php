<?php

declare(strict_types=1);

namespace Keboola\Gelf\StreamServer;

use Evenement\EventEmitter;
use Keboola\Gelf\Exception\InitException;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ServerInterface;

/** Emits the connection event */
class TcpStreamServer extends EventEmitter implements ServerInterface
{
    /**
     * Socket connection.
     * @var resource
     */
    public $master;

    /**
     * Event Loop.
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Server address
     * @var string
     */
    private string $address;

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
     * @throws InitException
     */
    public function listen(int $port, string $host = '0.0.0.0'): void
    {
        $this->address = $port . ':' . $host;
        if (str_contains($host, ':')) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $master = @stream_socket_server("tcp://$host:$port", $errorNumber, $errorString);
        if ($master === false) {
            $message = "Could not bind to tcp://$host:$port: $errorString";
            throw new InitException($message, $errorNumber);
        }
        $this->master = $master;
        stream_set_blocking($this->master, false);

        $this->loop->addReadStream($this->master, function ($master) {
            $newSocket = @stream_socket_accept($master);
            if ($newSocket === false) {
                $this->emit('error', [new InitException('Error accepting new connection')]);

                return;
            }
            $this->handleConnection($newSocket);
        });
    }

    /**
     * @param resource $socket
     */
    public function handleConnection($socket): void
    {
        stream_set_blocking($socket, false);
        $client = $this->createConnection($socket);
        $this->emit('connection', [$client]);
    }

    public function getPort(): int
    {
        $name = (string) stream_socket_get_name($this->master, false);
        return (int) substr((string) strrchr($name, ':'), 1);
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        $this->loop->removeReadStream($this->master);
        fclose($this->master);
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
