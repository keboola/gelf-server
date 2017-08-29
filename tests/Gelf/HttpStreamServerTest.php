<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Symfony\Component\Process\Process;

class HttpStreamServerTest extends AbstractGelfTest
{
    public function testServer()
    {
        $testsDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $server = ServerFactory::createServer(ServerFactory::SERVER_HTTP);
        $events = [];
        $process = new Process('php ' . $testsDir . 'Clients' . DIRECTORY_SEPARATOR . 'HttpClient.php');
        $server->start(
            12202,
            12202,
            function ($port) use ($process) {
                self::assertEquals(12202, $port);
                $process->start();
            },
            function (&$terminated) use ($process) {
                if (!$process->isRunning()) {
                    dump($process->getOutput());
                    $terminated = true;
                }
            },
            function ($event) use (&$events) {
                $events[] = $event;
            }
        );
        $this->checkResults($events);
    }
}
