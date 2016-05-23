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

        $process = new Process('php ' . $testsDir . 'clients' . DIRECTORY_SEPARATOR . 'TcpClient.php');
        $counter = 0;
        $server->start(
            12201,
            12201,
            function () use ($process) {
                $process->start();
            },
            function (&$terminated) use ($process) {
                if (!$process->isRunning()) {
                    $terminated = true;
                }
            },
            function ($event) use (&$counter, $testsDir) {
                $counter++;
                $file = $testsDir . 'data' . DIRECTORY_SEPARATOR . $counter . ".json";
                $target = json_decode(file_get_contents($file), true);

                if ($event['short_message'] == 'Exception example') {
                    $this->assertArrayHasKey('_exception', $event);
                    $this->assertArrayHasKey('file', $event);
                    unset($event['_exception']);
                    unset($target['_exception']);
                    unset($event['file']);
                    unset($target['file']);
                }
                $this->assertArrayHasKey('timestamp', $event);
                unset($event['timestamp']);
                unset($target['timestamp']);
                $this->assertArrayHasKey('host', $event);
                unset($target['host']);
                unset($event['host']);
                $this->assertEquals($target, $event);
            },
            function () use (&$counter) {
                $this->assertEquals(6, $counter);
            }
        );
    }
}
