<?php

namespace Keboola\Gelf;

use Keboola\Gelf\Socket\AbstractSocket;
use React\EventLoop\Factory;
use React\Socket\ConnectionException;

class Server #extends \React\Socket\Server
{
    /**
     * @var array
     */
    private $chunks = [];

    /**
     * @var AbstractSocket
     */
    private $socket;

    const BUFFER_SIZE = 65536;

    /**
     * @var \Keboola\Gelf\StreamServer
     */
    private $server;

    private function processBuffer($buffer)
    {

    }

    public function start($port, $onStart, $onProcess, $onTerminate, $onEvent)
    {
        $started = false;
        $terminated = false;

        $loop = \React\EventLoop\Factory::create();

        $this->server = new \Keboola\Gelf\StreamServer($loop);
        $server = $this->server;

        $loop->addPeriodicTimer(1, function () use ($onStart, $onProcess, $onTerminate, &$started, &$terminated, &$server, &$loop) {
            if (!$started) {
                $onStart();
                $started = true;
            } else {
                if ($terminated) {
                    $onTerminate();
                    $loop->stop();
                    $this->server->shutdown();
                } else {
                    $onProcess($terminated);
                    if ($terminated) {
                    }
                }
            }
            $memory = memory_get_usage() / 1024;
            $formatted = number_format($memory, 3).'K';
            dump("Current memory usage: {$formatted}");
        });

        $buffer = '';
        $this->server->on('connection', function (\React\Socket\Connection $conn) use ($onEvent, &$buffer) {
            #$conn->write("Hello there!\n");
            #$conn->write("Welcome to this amazing server!\n");
            #$conn->write("Here's a tip: don't say anything.\n");
            dump("On Connection");
            $conn->on('data', function ($data) use ($conn, $onEvent, &$buffer) {
                dump("On data");
                //file_put_contents("hovno", $data);
                $buffer .= $data;
                if (substr($buffer, -1) != "\x00") {
                    $partial = true;
                } else {
                    $partial = false;
                }
                $events = explode("\x00", $buffer);
                if ($partial) {
                    $buffer = array_pop($events);
                } else {
                    $buffer = '';
                }
                foreach ($events as $event) {
                    if ($event) {
                        $dataObject = json_decode($event, true);
                        foreach ($dataObject as $key => $value) {
                            if (substr($key, 0, 1) == '_') {
                                // custom field
                                $valueObject = json_decode($value, true);
                                if (json_last_error() == JSON_ERROR_NONE) {
                                    // successfully parsed
                                    $dataObject[$key] = $valueObject;
                                }
                            }
                        }
                        $onEvent($dataObject);
                    }
                }
                //dump($data);
            });
        });
        #$this->server->listenUdp(12201);
        $this->server->listen($port);

        $loop->run();
    }

    public function listen($port, $host = '127.0.0.1')
    {
        if (strpos($host, ':') !== false) {
            // enclose IPv6 addresses in square brackets before appending port
            $host = '[' . $host . ']';
        }

        $this->master = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            $message = "Could not bind to tcp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }
        stream_set_blocking($this->master, 0);

        $that = $this;
//
  //      $this->loop->addReadStream($this->master, function ($master) use ($that) {
            $newSocket = @stream_socket_accept($master);
            if (false === $newSocket) {
                $that->emit('error', array(new \RuntimeException('Error accepting new connection')));

                return;
            }
            $that->handleConnection($newSocket);
       // );
    }

  //  }
/*
    }


        $loop = Factory::create();
        $socket = new \React\Socket\Server($loop);
        $socket->on('connection', function ($conn) {
            $conn->write("Hello there!\n");
            $conn->write("Welcome to this amazing server!\n");
            $conn->write("Here's a tip: don't say anything.\n");

            $conn->on('data', function ($data) use ($conn) {
                $conn->close();
            });
        });
        $socket->listen(1337, $ipAddress);

        while (true) {
            if (!socket_recvfrom($this->socket->getResource(), $buffer, self::BUFFER_SIZE, 0, $remote_ip, $remote_port)) {

            }
    }
*/

    public function __construct()
    {
//        parent::__construct();
    }

    public function __constructd(AbstractSocket $socket)
    {
        $this->socket = $socket;

     //       $buf = $buf_part;
            #if ($r = socket_recvfrom($sock, $buf_part, 512, MSG_PEEK, $remote_ip, $remote_port)) {
//    while (1) {
            //   $buf = socket_read($sock, 100000000, PHP_BINARY_READ);
            //    if ($buf) {
            //      break;
            //}
            #}
            //}
            echo socket_strerror(socket_last_error());
            #var_dump($buf);
#    $buf = '';
            #   do {
            #      //$r = socket_recvfrom($sock, $buf_part, 512, 0, $remote_ip, $remote_port);
            #     $buf = socket_read($sock, -1, PHP_BINARY_READ)
            #    $buf .= $buf_part;
            #   echo "ret " . $r . "\n";
#    } while ($r > 0);

       //     echo "$remote_ip : $remote_port -- \n"; // . $buf;
//    var_dump($buf[0]);
            var_dump(ord($buf[0]));
            //  file_put_contents("pokus", $buf);
            switch (ord($buf[0])) {
                case 0x78: // zlib (deflate) message
                    echo "zlib\n";
                    echo gzuncompress ($buf);
                    #zlib.inflate(buf, this._broadcast.bind(this))
                    break;
                case 0x1f: // gzip message
                    echo "gzip\n";
                    echo gzdecode($buf);
                    #zlib.gunzip(buf, this._broadcast.bind(this))
                    break;
                case 0x1e: // chunked message
                    echo "chunked\n";
                    //   echo "\nchunkid: " . substr($buf, 0, 15);
                    $chunkId = substr($buf, 0, 10);
                    $index = ord(substr($buf, 10, 1));
                    $total =  ord(substr($buf, 11, 1));
                    echo "\nchunkid: " . $chunkId ;
                    echo "\nindex: " . ord(substr($buf, 10, 1));
                    echo "\ntotal: " . ord(substr($buf, 11, 1));
                    $chunks[base64_encode($chunkId)][] = substr($buf, 12);
                    file_put_contents("$index.txt", substr($buf, 12));
                    //var_dump($chunks);
                    if ($index == ($total - 1)) {
                        $buf = '';
                        echo "all chunks received\n";
                        foreach ($chunks[base64_encode($chunkId)] as $chunk) {
                            $buf .= $chunk;
                        }
                        //  var_dump($buf);
                        echo "\nob:" . (ord($buf[0]));
                        switch (ord($buf[0])) {
                            case 0x78: // zlib (deflate) message
                                echo "zlib\n";
                                echo gzuncompress($buf);
                                #zlib.inflate(buf, this._broadcast.bind(this))
                                break;
                            case 0x1f: // gzip message
                                echo "gzip\n";
                                echo gzdecode($buf);
                                #zlib.gunzip(buf, this._broadcast.bind(this))
                                break;
                            default:
                                echo "unknown\n";
                        }
                    }

                    //exit;
                    #this._handleChunk(buf)
                    break;
                default:   // unknown message
                    echo "unknown\n";
            }
            #break;
            //Send back the data to the client
            #socket_sendto($sock, "OK " . $buf , 100 , 0 , $remote_ip , $remote_port);
        }

     #   socket_close($sock);
    #}
}
