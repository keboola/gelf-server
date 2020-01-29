<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Symfony\Component\Process\Process;

class HttpStreamServerTest extends AbstractGelfTest
{
    public function testServer()
    {
        $server = ServerFactory::createServer(ServerFactory::SERVER_HTTP);
        $events = [];
        $fails = [];
        $process = new Process(
            'php ' . __DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'HttpClient.php'
        );
        $server->start(
            12202,
            12202,
            function ($port) use ($process) {
                self::assertEquals(12202, $port);
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
                'Cannot parse JSON data in event: "Syntax error". Data: "POST /gelf HTTP/1.1".',
                'Cannot parse JSON data in event: "Syntax error". Data: "Content-Length: 193".',
                'Cannot parse JSON data in event: "Syntax error". Data: "POST /gelf HTTP/1.1".',
                'Cannot parse JSON data in event: "Syntax error". Data: "Content-Length: 16".',
                'Cannot parse JSON data in event: "Syntax error". Data: "complete garbage".',
                'Cannot parse JSON data in event: "Syntax error". Data: "POST /gelf HTTP/1.1".',
                'Cannot parse JSON data in event: "Syntax error". Data: "Content-Length: 193".',
                'Cannot parse JSON data in event: "Syntax error". Data: "garbage".',
            ],
            $fails
        );
    }
}
