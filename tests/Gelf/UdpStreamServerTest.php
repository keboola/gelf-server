<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\ServerFactory;
use Keboola\Gelf\StreamServer;
use Symfony\Component\Process\Process;

class UdpStreamServerTest extends \PHPUnit_Framework_TestCase
{
    public function testServer()
    {
        $testsDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $server = ServerFactory::createServer(ServerFactory::SERVER_UDP);

        $process = new Process('php ' . $testsDir . 'Clients' . DIRECTORY_SEPARATOR . 'UdpClient.php');
        $counter = 0;
        $server->start(
            12201,
            12201,
            function ($port) use ($process) {
                $this->assertEquals(12201, $port);
                $process->start();
            },
            function (&$terminated) use ($process) {
                if (!$process->isRunning()) {
                    dump($process->getOutput());
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
                unset($event['host']);
                unset($target['host']);
                $this->assertEquals($target, $event);
            },
            function () use (&$counter) {
                $this->assertEquals(6, $counter);
            }
        );
    }
}
