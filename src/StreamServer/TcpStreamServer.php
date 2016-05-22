<?php

namespace Keboola\Gelf\StreamServer;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ConnectionException;
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
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $this->master = stream_socket_server("tcp://$host:$port", $errorNumber, $errorString);
        if (false === $this->master) {
            $message = "Could not bind to tcp://$host:$port: $errorString";
            throw new ConnectionException($message, $errorNumber);
        }
        stream_set_blocking($this->master, 0);

        $this->loop->addReadStream($this->master, function ($master) {
            $newSocket = @stream_socket_accept($master);
            if (false === $newSocket) {
                $this->emit('error', array(new \RuntimeException('Error accepting new connection')));

                return;
            }
            $this->handleConnection($newSocket);
        });
    }

    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);
        $client = $this->createConnection($socket);
        $this->emit('connection', array($client));
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->master, false);
        return (int) substr(strrchr($name, ':'), 1);
    }

    public function shutdown()
    {
        $this->loop->removeStream($this->master);
        fclose($this->master);
        $this->removeAllListeners();
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }
}
