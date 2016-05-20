<?php

namespace Keboola\Gelf\Tests;

use Keboola\Gelf\Server;
use Keboola\Gelf\StreamServer;
use Symfony\Component\Process\Process;

class TcpStreamServerTest extends \PHPUnit_Framework_TestCase
{
    public function testServer()
    {
        $server = new Server();

        $process = new Process('php ' . ROOT_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'clients' . DIRECTORY_SEPARATOR . 'TcpClient.php');
        $cntr = 0;
        $server->start(
            function () use ($process) {
                dump("server started");
                $process->start();
            },
            function (&$terminated) use(&$cntr, $process) {
                dump("server running $cntr");
                //$cntr++;
                if ($cntr < 0) {
                    //$terminated = true;
                }
                if ($process->isRunning()) {
                    dump("Client is running");
                } else {
                    dump("Client output");
                    dump($process->getOutput());
                    $terminated = true;
                }
            },
            function () {
                dump("server terminated");
            },
            function ($event) use (&$cntr) {
                $cntr++;
                $file = __DIR__ . DIRECTORY_SEPARATOR . $cntr . ".json";
                dump($file);
                //file_put_contents($file, json_encode($event, JSON_PRETTY_PRINT));
                $mustr = json_decode(file_get_contents($file), true);
                unset($mustr['timestamp']);
                unset($event['timestamp']);
                $this->assertEquals($mustr, $event);
                //dump($event);
            }
        );
    }


    public function testServerPorts()
    {
        $server1 = new Server();
        dump('starting server 2');
        $server1->start(
            12201,
            function () {
              // sleep(10);
                dump('server 2 started');
            }, function () {
                dump('server 2 running');
            }, function () {
                dump('server 2 terminated');
            }, function () {
                dump('server 2 message');
            }
        );
    }
}
