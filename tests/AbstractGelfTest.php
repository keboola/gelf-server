<?php

namespace Keboola\Gelf\Tests;

abstract class AbstractGelfTest extends \PHPUnit_Framework_TestCase
{
    protected function checkResults(array $events)
    {
        $timestamps = [];
        $hosts = [];
        $file = '';
        $line = '';
        $exception = '';
        foreach ($events as $event) {
            self::assertArrayHasKey('timestamp', $event);
            self::assertArrayHasKey('host', $event);
            $timestamps[] = $event['timestamp'];
            $hosts[] = $event['host'];
            if (!empty($event['file'])) {
                $file = $event['file'];
                $exception = $event['_exception'];
                $line = $event['line'];
            }
        }
        self::assertEquals([
            0 => [
                'version' => '1.0',
                'host' => $hosts[0],
                'short_message' => 'A debug message.',
                'level' => 7,
                'timestamp' => $timestamps[0],
            ],
            1 => [
                'version' => '1.0',
                'host' => $hosts[1],
                'short_message' => 'An alert message',
                'level' => 1,
                'timestamp' => $timestamps[1],
                '_structure' => [
                    'data' => [0, 1]
                ]
            ],
            2 => [
                'version' => '1.0',
                'host' => $hosts[2],
                'short_message' => 'Exception example',
                'level' => 0,
                'timestamp' => $timestamps[2],
                'full_message' => "Exception: Test exception (0)\n\n#0 {main}\n",
                'file' => $file,
                'line' => $line,
                '_exception' => $exception,
            ],
            3 => [
                'version' => '1.0',
                'host' => $hosts[3],
                'short_message' => 'Structured message',
                'level' => 1,
                'timestamp' => $timestamps[3],
                'full_message' => 'There was a foo in bar',
                'facility' => 'example-facility',
                '_foo' => 'bar',
                '_bar' => 'baz',
            ],
            4 => [
                'version' => '1.0',
                'host' => $hosts[4],
                'short_message' => 'A warning message.',
                'level' => 4,
                'timestamp' => $timestamps[4],
                '_structure' => [
                    'with' => [
                        'several' => 'nested',
                        0 => 'levels',
                    ]
                ],
            ],
            5 => [
                'version' => '1.0',
                'host' => $hosts[5],
                'short_message' =>
                    file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'bacon.txt'),
                'level' => 6,
                'timestamp' => $timestamps[5],
            ]
        ], $events);
    }
}
