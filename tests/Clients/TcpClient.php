<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../vendor/autoload.php';

$server = "127.0.0.1";
$port = 12201;
$transport = new Gelf\Transport\TcpTransport($server, $port);

$publisher = new Gelf\Publisher();
$publisher->addTransport($transport);

$logger = new Gelf\Logger($publisher);

$logger->debug("A debug message.");
$logger->alert("An alert message", ['structure' => ['data' => [0, 1]]]);

try {
    throw new Exception("Test exception");
} catch (Exception $e) {
    $logger->emergency("Exception example", ['exception' => $e]);
}

$message = new Gelf\Message();
$message->setShortMessage("Structured message")
    ->setLevel(\Psr\Log\LogLevel::ALERT)
    ->setFullMessage("There was a foo in bar")
    ->setFacility("example-facility")
    ->setAdditional('foo', 'bar')
    ->setAdditional('bar', 'baz')
;
$publisher->publish($message);

$logger->warning("A warning message.", ['structure' => ['with' => ['several' => 'nested', 'levels']]]);
$logger->info(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "bacon.txt"));
unset($logger);

// manually create a socket to send some garbage in
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    $errorCode = socket_last_error();
    $errorMsg = socket_strerror($errorCode);
    echo "Cannot create socket: [$errorCode] $errorMsg";
}
if (socket_connect($socket, $server, $port) === false) {
    $errorCode = socket_last_error();
    $errorMsg = socket_strerror($errorCode);
    echo "Cannot connect to socket: [$errorCode] $errorMsg";
}

$buff = '{"version":"1.0","short_message":"A message without host","level":7,"timestamp":1504008347}' . "\x00";
socket_send($socket, $buff, strlen($buff), 0);
$buff = '{"version":"1.0","short_message":"First message","level":7,"timestamp":1504008347}' . "\n" .
        '{"version":"1.0","short_message":"Second message","level":7,"timestamp":1504001234}' . "\x00";
socket_send($socket, $buff, strlen($buff), 0);
$buff = 'complete garbage' . "\x00";
socket_send($socket, $buff, strlen($buff), 0);

socket_close($socket);
