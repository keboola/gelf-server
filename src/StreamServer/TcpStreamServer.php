<?php

declare(strict_types=1);

namespace Keboola\Gelf\StreamServer;

use Evenement\EventEmitter;
use Keboola\Gelf\Exception\InitException;
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
    private $loop;

    /**
     * Server address
     * @var string
     */
    private $address;

    /**
     * IS the server paused
     * @var bool
     */
    private $isPaused;

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
    public function listen($port, $host = '0.0.0.0')
    {
        $this->address = $port . ':' . $host;
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $this->master = @stream_socket_server("tcp://$host:$port", $errorNumber, $errorString);
        if (false === $this->master) {
            $message = "Could not bind to tcp://$host:$port: $errorString";
            throw new InitException($message, $errorNumber);
        }
        stream_set_blocking($this->master, false);

        $this->loop->addReadStream($this->master, function ($master) {
            $newSocket = @stream_socket_accept($master);
            if (false === $newSocket) {
                $this->emit('error', [new InitException('Error accepting new connection')]);

                return;
            }
            $this->handleConnection($newSocket);
        });
    }

    public function handleConnection($socket)
    {
        stream_set_blocking($socket, false);
        $client = $this->createConnection($socket);
        $this->emit('connection', [$client]);
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->master, false);
        return (int) substr(strrchr($name, ':'), 1);
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        $this->loop->removeReadStream($this->master);
        fclose($this->master);
        $this->removeAllListeners();
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @inheritdoc
     */
    public function pause()
    {
        $this->isPaused = true;
    }

    /**
     * @inheritdoc
     */
    public function resume()
    {
        $this->isPaused = false;
    }
}
