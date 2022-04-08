<?php

declare(strict_types=1);

namespace Keboola\Gelf;

use Keboola\Gelf\StreamServer\TcpStreamServer;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;

class TcpServer extends AbstractServer
{
    /**
     * @inheritdoc
     */
    public function start(
        $minPort,
        $maxPort,
        callable $onStart,
        callable $onProcess,
        callable $onEvent,
        callable $onTerminate = null,
        callable $onError = null
    ) {
        $started = false;
        $terminated = false;
        $port = '';
        $buffer = '';

        $loop = Factory::create();
        $this->server = new TcpStreamServer($loop);
        $loop->addPeriodicTimer(
            1,
            function () use ($onStart, $onProcess, $onTerminate, $onEvent, $onError, &$started, &$terminated, &$loop, &$port, &$buffer) {
                if (!$started) {
                    $onStart($port);
                    $started = true;
                } else {
                    if ($terminated) {
                        if ($buffer != '') {
                            $this->processEvents([$buffer], $onEvent, $onError);
                        }
                        if ($onTerminate) {
                            $onTerminate();
                        }
                        $loop->stop();
                        $this->server->close();
                    } else {
                        $onProcess($terminated);
                    }
                }
            }
        );

        $this->server->on('connection', function (ConnectionInterface $conn) use ($onEvent, $onError, &$buffer) {
            $conn->on('data', function ($data) use ($conn, $onEvent, $onError, &$buffer) {
                $buffer .= $data;
                $events = explode("\x00", $buffer);
                if (substr($buffer, -1) != "\x00") {
                    // buffer is unfinished, take last message and put it into next buffer
                    $buffer = array_pop($events);
                } else {
                    // buffer is finished, clear it
                    $buffer = '';
                }
                $this->processEvents($events, $onEvent, $onError);
            });
        });

        $this->startServer($minPort, $maxPort, $port);
        $loop->run();
    }
}
