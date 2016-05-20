<?php

$errno = '';
$errstr = '';

$master = stream_socket_server("tcp://127.0.0.1:12201", $errno, $errstr);
//stream_set_blocking($master, 0);
var_dump($errno);
var_dump($errstr);
var_dump($master);
while (true) {

};

/*
$master2 = stream_socket_server("tcp://127.0.0.1:12201", $errno2, $errstr2);
stream_set_blocking($master2, 0);
var_dump($errno2);
var_dump($errstr2);
var_dump($master2);

$master3 = fsockopen('tcp://127.0.0.1', '12201', $errno3, $errst3);
stream_set_blocking($master3, 0);
var_dump($errno3);
var_dump($errst3);
var_dump($master3);

*/