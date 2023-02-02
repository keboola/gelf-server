<?php

declare(strict_types=1);

namespace Keboola\Gelf;

use Keboola\Gelf\Exception\InitException;
use Keboola\Gelf\Exception\InvalidMessageException;
use Keboola\Gelf\StreamServer\TcpStreamServer;
use Keboola\Gelf\StreamServer\UdpStreamServer;
use React\Socket\ServerInterface;

abstract class AbstractServer
{
    /**
     * Number of retries for starting the server.
     */
    private const SERVER_START_RETRIES = 10;

    /** @var TcpStreamServer|UdpStreamServer */
    protected ServerInterface $server;

    /**
     * Find a free port for the server and start it.
     * @param int $minPort Min port in range (inclusive).
     * @param int $maxPort Max port in range (inclusive).
     * @param ?int $port Actual selected port (output).
     */
    protected function startServer(int $minPort, int $maxPort, ?int &$port): void
    {
        if ($minPort > $maxPort) {
            throw new InitException("Invalid port range min ($minPort) is bigger than max ($maxPort).");
        }

        $retries = 0;
        $connected = false;
        while (!$connected && ($retries < self::SERVER_START_RETRIES)) {
            $port = rand($minPort, $maxPort);
            try {
                $this->server->listen($port);
                $connected = true;
            } catch (InitException $e) {
                $retries++;
                if ($retries >= self::SERVER_START_RETRIES) {
                    throw new InitException('Failed to start server ' . $e->getMessage(), $e->getCode(), $e);
                }
            }
        }
    }

    /**
     * Process actual event data.
     * @param string $data Event data.
     * @return array Parsed event.
     */
    protected function processEventData(string $data): array
    {
        $dataObject = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidMessageException(
                'Cannot parse JSON data in event: "' . json_last_error_msg() . '".',
                $data
            );
        }
        if (!is_array($dataObject)) {
            throw new InvalidMessageException(
                'Message data is not array: "' . var_export($dataObject, true) . '".',
                $data
            );
        }
        if (!$dataObject) {
            return [];
        }
        foreach ($dataObject as $key => $value) {
            if (str_starts_with($key, '_')) {
                // custom field may get double encoded
                if (is_array($value)) {
                    $dataObject[$key] = $value;
                } else {
                    $valueObject = json_decode((string) $value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // successfully parsed
                        $dataObject[$key] = $valueObject;
                    } // else not a json, leave as is
                }
            } // else not a custom field, leave as is
        }
        return $dataObject;
    }

    protected function processEvents(array $events, callable $onEvent, ?callable $onError): void
    {
        foreach ($events as $event) {
            if ($event) {
                try {
                    $dataObject = $this->processEventData($event);
                    $onEvent($dataObject);
                } catch (InvalidMessageException $e) {
                    // try the message split in lines
                    $lines = explode("\n", $event);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }
                        try {
                            $dataObject = $this->processEventData($line);
                            $onEvent($dataObject);
                        } catch (InvalidMessageException $ex) {
                            if ($onError) {
                                $onError($ex->getMessage() . ' Data: "' . $line . '".');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Start a TCP GELF Server.
     * @param int $minPort Lowest allowed port number to listen on.
     * @param int $maxPort Highest allowed port number to listen on.
     * @param callable $onStart Callback executed once when server is started.
     * @param callable $onProcess Callback executed periodically when server is running.
     * @param callable $onEvent Callback executed when a message is received.
     * @param ?callable $onTerminate Callback executed when server is terminated.
     * @param ?callable $onError Callback executed when an invalid event is encountered.
     */
    abstract public function start(
        int $minPort,
        int $maxPort,
        callable $onStart,
        callable $onProcess,
        callable $onEvent,
        ?callable $onTerminate = null,
        ?callable $onError = null
    ): void;
}
