<?php

use Keboola\Gelf\ServerFactory;

require('../vendor/autoload.php');

$server = ServerFactory::createServer(ServerFactory::SERVER_UDP);
$server->start(
    12202,
    12202,
    function ($port) {
        echo "UDP Server listening on port $port ";
    },
    function () {
        echo ".";
    },
    function ($event) {
        var_dump($event);
    }
);
    