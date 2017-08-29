<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Symfony\Component\Process\Process;

class TcpStreamServerTest extends AbstractGelfTest
{
    public function testServer()
    {
        $testsDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $server = ServerFactory::createServer(ServerFactory::SERVER_TCP);
        $events = [];
        $process = new Process('php ' . $testsDir . 'Clients' . DIRECTORY_SEPARATOR . 'TcpClient.php');
        $server->start(
            12201,
            12201,
            function ($port) use ($process) {
                self::assertEquals(12201, $port);
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
