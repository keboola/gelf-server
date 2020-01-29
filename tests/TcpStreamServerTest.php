<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\Exception\InitException;
use Keboola\Gelf\ServerFactory;
use Symfony\Component\Process\Process;

class TcpStreamServerTest extends AbstractGelfTest
{
    public function testServer()
    {
        $server = ServerFactory::createServer(ServerFactory::SERVER_TCP);
        $events = [];
        $fails = [];
        $process = new Process(
            'php ' . __DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'TcpClient.php'
        );
        $server->start(
            12201,
            12201,
            function ($port) use ($process) {
                self::assertEquals(12201, $port);
                $process->start();
            },
            function (&$terminated) use ($process) {
                if (!$process->isRunning()) {
                    $terminated = true;
                }
            },
            function ($event) use (&$events) {
                $events[] = $event;
            },
            null,
            function ($event) use (&$fails) {
                $fails[] = $event;
            }
        );
        $this->checkResults($events);
        self::assertEquals(
            [
                'Cannot parse JSON data in event: "Syntax error". Data: "complete garbage".',
                'Cannot parse JSON data in event: "Syntax error". Data: "even more garbage without null".',
            ],
            $fails
        );
    }


    public function testServerFail()
    {
        $server = ServerFactory::createServer(ServerFactory::SERVER_TCP);
        $process = new Process(
            'php ' . __DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'TcpClient.php'
        );
        self::expectException(InitException::class);
        self::expectExceptionMessage('Could not bind to tcp://0.0.0.0:12201: Address already in use');
        $server->start(
            12201,
            12201,
            function () use ($process) {
                $process->start();
                $server = ServerFactory::createServer(ServerFactory::SERVER_TCP);
                $server->start(
                    12201,
                    12201,
                    function () {
                    },
                    function () {
                    },
                    function () {
                    }
                );
            },
            function (&$terminated) use ($process) {
                if (!$process->isRunning()) {
                    $terminated = true;
                }
            },
            function () {
            }
        );
    }
}
