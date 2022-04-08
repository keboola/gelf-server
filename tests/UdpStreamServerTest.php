<?php

declare(strict_types=1);

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Symfony\Component\Process\Process;

class UdpStreamServerTest extends AbstractGelfTest
{
    public function testServer(): void
    {
        $server = ServerFactory::createServer(ServerFactory::SERVER_UDP);
        $events = [];
        $fails = [];
        $process = new Process(
            ['php', __DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'UdpClient.php']
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
        self::assertEquals(['Cannot parse JSON data in event: "Syntax error". Data: "complete garbage".'], $fails);
    }
}
