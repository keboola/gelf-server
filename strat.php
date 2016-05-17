<?php

require 'vendor/autoload.php';
require 'src/Keboola/Gelf/StreamServer.php';

$loop = \React\EventLoop\Factory::create();

$loop->addPeriodicTimer(5, function () {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});

$socket = new \Keboola\Gelf\StreamServer($loop);
$socket->on('connection', function (\React\Socket\Connection $conn) {
    $conn->write("Hello there!\n");
    $conn->write("Welcome to this amazing server!\n");
    $conn->write("Here's a tip: don't say anything.\n");

    $conn->on('data', function ($data) use ($conn) {

        file_put_contents("hovno", $data);
        echo $data;
    });
});
$socket->listen(12201);

$loop->run();

while (true) {
    print ("waiting");
    sleep(1);
}
