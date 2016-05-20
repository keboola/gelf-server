<?php

require 'vendor/autoload.php';
require 'src/Keboola/Gelf/StreamServer.php';
require 'src/Keboola/Gelf/Server.php';

$server1 = new Keboola\Gelf\Server();
dump('starting server 1');
$server1->start(
    12201,
    function () {
      // sleep(10);

    }, function () {
        dump('server 1 running');
    }, function () {
        dump('server 1 terminated');
    }, function () {
        dump('server 1 message');
    }
);
