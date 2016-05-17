<?php

namespace Keboola\Gelf;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ConnectionException;
use React\Socket\ServerInterface;


/** Emits the connection event */
class StreamServer extends EventEmitter implements ServerInterface
{
    public $master;
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }




    // todo presunout do subclass
    public function listen($port, $host = '127.0.0.1')
    {
        $host = '0.0.0.0';
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr/*, STREAM_SERVER_BIND*/);
        if (false === $this->master) {
            $message = "Could not bind to tcp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }
        stream_set_blocking($this->master, 0);

        $that = $this;

        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            $newSocket = @stream_socket_accept($master);
            if (false === $newSocket) {
                $that->emit('error', array(new \RuntimeException('Error accepting new connection')));

                return;
            }
            $that->handleConnection($newSocket);
        });
    }

    public function listenUdp($port, $host = '127.0.0.1')
    {
        $host = '0.0.0.0';
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $this->master = stream_socket_server("udp://$host:$port", $errno, $errstr, STREAM_SERVER_BIND);
        if (false === $this->master) {
            $message = "Could not bind to udp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }
        stream_set_blocking($this->master, 0);

        $that = $this;

        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            #$newSocket = @stream_socket_accept($master);
            //$this->emit('connection', array());
            // @todo
            $data = stream_socket_recvfrom($this->master, 1500);
#            if (false === $newSocket) {
 #               $that->emit('error', array(new \RuntimeException('Error accepting new connection')));
#
 #               return;
  #          }
   #         $that->handleConnection($newSocket);
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
