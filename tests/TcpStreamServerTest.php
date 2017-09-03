<?php

namespace Keboola\Gelf\Tests;

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
        self::assertEquals(['complete garbage', 'even more garbage without null'], $fails);
    }
}
