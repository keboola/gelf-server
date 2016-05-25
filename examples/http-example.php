<?php

use Keboola\Gelf\ServerFactory;

require('../vendor/autoload.php');

$server = ServerFactory::createServer(ServerFactory::SERVER_HTTP);
$server->start(
    12202,
    12202,
    function ($port) {
        echo "HTTP Server listening on port $port ";
    },
    function () {
        echo ".";
    },
    function ($event) {
        var_dump($event);
    }
);
    