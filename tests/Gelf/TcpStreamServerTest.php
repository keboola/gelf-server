<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Keboola\Gelf\StreamServer;
use Symfony\Component\Process\Process;

class TcpStreamServerTest extends \PHPUnit_Framework_TestCase
{
    public function testServer()
    {
        $testsDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $server = ServerFactory::createServer(ServerFactory::SERVER_TCP);

        $process = new Process('php ' . $testsDir . 'Clients' . DIRECTORY_SEPARATOR . 'TcpClient.php');
        $counter = 0;
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
            },
            null
        );
        $timestamps = [];
        $hosts = [];
        foreach ($events as $event) {
            self::assertArrayHasKey('timestamp', $event);
            self::assertArrayHasKey('host', $event);
            $timestamps[] = $event['timestamp'];
            $hosts[] = $event['host'];
        }
        self::assertEquals([
            0 => [
                'version' => '1.0',
                'host' => $hosts[0],
                'short_message' => 'A debug message.',
                'level' => 7,
                'timestamp' => $timestamps[0],
            ],
            1 => [
                'version' => '1.0',
                'host' => $hosts[1],
                'short_message' => 'An alert message',
                'level' => 1,
                'timestamp' => $timestamps[1],
                '_structure' => [
                    'data' => [0, 1]
                ]
            ],
            2 => [
                'version' => '1.0',
                'host' => $hosts[2],
                'short_message' => 'Exception example',
                'level' => 0,
                'timestamp' => $timestamps[2],
                'full_message' => "Exception: Test exception (0)\n\n#0 {main}\n",
                'file' => 'D:\Dropbox\wwwroot\gelf-server\tests\Clients\TcpClient.php',
                'line' => 18,
                '_exception' => "Exception: Test exception in D:\Dropbox\wwwroot\gelf-server" .
                    "\\tests\Clients\TcpClient.php:18\nStack trace:\n#0 {main}",
            ],
            3 => [
                'version' => '1.0',
                'host' => $hosts[3],
                'short_message' => 'Structured message',
                'level' => 1,
                'timestamp' => $timestamps[3],
                'full_message' => 'There was a foo in bar',
                'facility' => 'example-facility',
                '_foo' => 'bar',
                '_bar' => 'baz',
            ],
            4 => [
                'version' => '1.0',
                'host' => $hosts[4],
                'short_message' => 'A warning message.',
                'level' => 4,
                'timestamp' => $timestamps[4],
                '_structure' => [
                    'with' => [
                        'several' => 'nested',
                        0 => 'levels',
                    ]
                ],
            ],
            5 => [
                'version' => '1.0',
                'host' => $hosts[5],
                'short_message' =>
                    file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' .
                        DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'bacon.txt'),
                'level' => 6,
                'timestamp' => $timestamps[5],
            ]
        ], $events);
    }
}
