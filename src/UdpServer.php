<?php

namespace Keboola\Gelf;

use Keboola\Gelf\Exception\InvalidMessageException;
use Keboola\Gelf\StreamServer\UdpStreamServer;
use React\EventLoop\Factory;

class UdpServer extends AbstractServer
{
    const BUFFER_SIZE = 65536;

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

        $loop = Factory::create();
        $this->server = new UdpStreamServer($loop);

        $countDown = 3;
        $loop->addPeriodicTimer(
            1,
            function () use ($onStart, $onProcess, $onTerminate, &$started, &$terminated, &$loop, &$port, &$countDown) {
                if (!$started) {
                    $onStart($port);
                    $started = true;
                } else {
                    if ($terminated) {
                        $countDown--;
                        if ($countDown < 0) {
                            if ($onTerminate) {
                                $onTerminate();
                            }
                            $loop->stop();
                            $this->server->shutdown();
                        }
                    } else {
                        $onProcess($terminated);
                    }
                }
            }
        );

        $chunks = [];
        $this->server->on('data', function ($data) use ($onEvent, $onError, &$chunks) {
            $dataDecoded = $this->processData($data, $chunks);
            if ($dataDecoded) {
                $this->processEvents([$dataDecoded], $onEvent, $onError);
            }
        });

        $this->startServer($minPort, $maxPort, $port);
        $loop->run();
    }

    /**
     * @param string $data Raw data from source.
     * @param array $chunks Array containing already received chunks.
     * @return string Decoded data.
     */
    private function processData($data, &$chunks)
    {
        switch (ord($data[0])) {
            case 0x78:
                // Z-LIB (deflate) message
                $dataDecoded = gzuncompress($data);
                if ($dataDecoded === false) {
                    throw new InvalidMessageException("Cannot GZ uncompress datagram.", $data);
                }
                break;
            case 0x1f:
                // Gzipped message
                $dataDecoded = gzdecode($data);
                if ($dataDecoded === false) {
                    throw new InvalidMessageException("I Cannot GZ decode datagram.", $data);
                }
                break;
            case 0x1e:
                // chunked message
                $chunkId = substr($data, 0, 10);
                $index = ord(substr($data, 10, 1));
                $total =  ord(substr($data, 11, 1));
                $chunks[base64_encode($chunkId)][] = substr($data, 12);
                if ($index == ($total - 1)) {
                    $dataJoined = '';
                    foreach ($chunks[base64_encode($chunkId)] as $chunk) {
                        $dataJoined .= $chunk;
                    }
                    $dataDecoded = $this->processData($dataJoined, $chunks);
                    unset($chunks[base64_encode($chunkId)]);
                } else {
                    $dataDecoded = '';
                }
                break;
            default:
                // unknown message
                throw new InvalidMessageException("Unknown message type.", $data);
        }
        return $dataDecoded;
    }
}
