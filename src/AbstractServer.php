<?php

namespace Keboola\Gelf;

use Keboola\Gelf\Exception\InitException;
use Keboola\Gelf\Exception\InvalidMessageException;
use React\Socket\ConnectionException;
use React\Socket\ServerInterface;

abstract class AbstractServer
{
    /**
     * Number of retries for starting the server.
     */
    const SERVER_START_RETRIES = 10;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * Find a free port for the server and start it.
     * @param int $minPort Min port in range (inclusive).
     * @param int $maxPort Max port in range (inclusive).
     */
    protected function startServer($minPort, $maxPort)
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
            } catch (ConnectionException $e) {
                $retries++;
                if ($retries >= self::SERVER_START_RETRIES) {
                    throw new InitException("Failed to start server " . $e->getMessage(), $e);
                }
            }
        }
    }

    /**
     * Process actual event data.
     * @param string $data Event data.
     * @return array Parsed event.
     */
    protected function processEventData($data)
    {
        $dataObject = json_decode($data, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new InvalidMessageException(
                "I Cannot parse JSON data in event - error: " . json_last_error(),
                $data
            );
        }
        foreach ($dataObject as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                // custom field may get double encoded
                $valueObject = json_decode($value, true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    // successfully parsed
                    $dataObject[$key] = $valueObject;
                } // else not a json, leave as is
            } // else not a custom field, leave as is
        }
        return $dataObject;
    }

    /**
     * Start a TCP GELF Server.
     * @param int $minPort Lowest allowed port number to listen on.
     * @param int $maxPort Highest allowed port number to listen on.
     * @param callable $onStart Callback executed once when server is started.
     * @param callable $onProcess Callback executed periodically when server is running.
     * @param callable $onEvent Callback executed when a message is received.
     * @param callable $onTerminate Callback executed when server is terminated.
     */
    abstract public function start(
        $minPort,
        $maxPort,
        callable $onStart,
        callable $onProcess,
        callable $onEvent,
        callable $onTerminate = null
    );
}
