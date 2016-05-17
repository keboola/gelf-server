<?php

require_once __DIR__ . '../../vendor/autoload.php';

$transport = new Gelf\Transport\TcpTransport("127.0.0.1", 12201);

$publisher = new Gelf\Publisher();
$publisher->addTransport($transport);

$logger = new Gelf\Logger($publisher);

$logger->debug("A debug message.");
$logger->alert("An alert message");

try {
    throw new Exception("Test exception");
} catch (Exception $e) {
    $logger->emergency("Exception example", array('exception' => $e));
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

$logger->warning("A warning message.", ['a' => 'b']);
$logger->info(file_get_contents("bacon.txt"));
