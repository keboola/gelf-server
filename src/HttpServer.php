<?php

declare(strict_types=1);

namespace Keboola\Gelf;

use Keboola\Gelf\Exception\InvalidMessageException;
use Keboola\Gelf\StreamServer\TcpStreamServer;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;

class HttpServer extends AbstractServer
{
    /**
     * @inheritdoc
     */
    public function start(
        int $minPort,
        int $maxPort,
        callable $onStart,
        callable $onProcess,
        callable $onEvent,
        ?callable $onTerminate = null,
        ?callable $onError = null
    ): void {
        $started = false;
        $terminated = false;
        $port = null;

        $loop = Factory::create();
        $this->server = new TcpStreamServer($loop);
        $loop->addPeriodicTimer(
            1,
            function () use ($onStart, $onProcess, $onTerminate, &$started, &$terminated, &$loop, &$port) {
                if (!$started) {
                    $onStart($port);
                    $started = true;
                } else {
                    // @phpstan-ignore-next-line PHPStan doesn't recognize, that the value can change
                    if ($terminated) {
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

        $buffer = '';
        $contentLength = null;
        $this->server->on('connection', function (ConnectionInterface $conn) use ($onEvent, $onError, &$buffer): void {
            $conn->on('data', function (string $data) use ($conn, $onEvent, $onError, &$buffer, &$contentLength): void {
                $headers = substr($data, 0, (int) strpos($data, "\r\n\r\n"));
                if ($headers) {
                    $headers = explode("\r\n", $headers);
                    foreach ($headers as $header) {
                        if (substr($header, 0, strlen('Content-Length')) === 'Content-Length') {
                            $contentLength = substr($header, strlen('Content-Length: '));
                            break;
                        }
                    }
                    $messageData = substr($data, strpos($data, "\r\n\r\n") + 4);
                } else {
                    // split message, the whole message is content
                    $messageData = $buffer . $data;
                }
                $length = strlen($messageData);
                if (!$contentLength) {
                    throw new InvalidMessageException('Unknown content length.', $data);
                }

                if ($length < $contentLength) {
                    // buffer is unfinished, take last message and put it into next buffer
                    $buffer = $messageData;
                    $messageData = '';
                } else {
                    // buffer is finished, clear it
                    $buffer = '';
                    $contentLength = null;
                }
                if ($messageData) {
                    $this->processEvents([$messageData], $onEvent, $onError);
                    $conn->write(
                        "HTTP/1.1 202 Accepted\r\nConnection: Keep-Alive\r\nContent-Type: application/json\r\n\r\n"
                    );
                }
            });
        });

        $this->startServer($minPort, $maxPort, $port);
        $loop->run();
    }
}
