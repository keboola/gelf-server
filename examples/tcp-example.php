<?php

use Keboola\Gelf\ServerFactory;

require('../vendor/autoload.php');

$server = ServerFactory::createServer(ServerFactory::SERVER_TCP);
$server->start(
    12202,
    12202,
    function ($port) {
        echo "TCP Server listening on port $port ";
    },
    function (&$terminated) {
        echo ".";
    },
    function ($event) {
        var_dump($event);
    }
);
