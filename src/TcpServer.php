<?php

namespace Keboola\Gelf;

use Keboola\Gelf\StreamServer\TcpStreamServer;
use React\EventLoop\Factory;
use React\Socket\Connection;

class TcpServer extends AbstractServer
{
    /**
     * @inheritdoc
     */
    public function start($minPort, $maxPort, callable $onStart, callable $onProcess, callable $onEvent, callable $onTerminate = null)
    {
        $started = false;
        $terminated = false;
        $port = '';

        $loop = Factory::create();
        $this->server = new TcpStreamServer($loop);
        $loop->addPeriodicTimer(
            1,
            function () use ($onStart, $onProcess, $onTerminate, &$started, &$terminated, &$loop, &$port) {
                if (!$started) {
                    $onStart($port);
                    $started = true;
                } else {
                    if ($terminated) {
                        if ($onTerminate) {
                            $onTerminate();
                        }
                        $loop->stop();
                        $this->server->shutdown();
                    } else {
                        $onProcess($terminated);
                    }
                }
            }
        );

        $buffer = '';
        $this->server->on('connection', function (Connection $conn) use ($onEvent, &$buffer) {
            $conn->on('data', function ($data) use ($conn, $onEvent, &$buffer) {
                $buffer .= $data;
                $events = explode("\x00", $buffer);
                if (substr($buffer, -1) != "\x00") {
                    // buffer is unfinished, take last message and put it into next buffer
                    $buffer = array_pop($events);
                } else {
                    // buffer is finished, clear it
                    $buffer = '';
                }
                foreach ($events as $event) {
                    if ($event) {
                        $dataObject = $this->processEventData($event);
                        $onEvent($dataObject);
                    }
                }
            });
        });

        $this->startServer($minPort, $maxPort);
        $loop->run();
    }
}
