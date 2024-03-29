<?php

declare(strict_types=1);

namespace Keboola\Gelf\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractGelfTest extends TestCase
{
    protected function checkResults(array $events): void
    {
        $timestamps = [];
        $hosts = [];
        $file = '';
        $line = '';
        $exception = '';
        foreach ($events as $event) {
            self::assertArrayHasKey('timestamp', $event);
            self::assertTrue(!empty($event['host']) || ($event['short_message'] === 'No host'));
            $timestamps[] = $event['timestamp'];
            $hosts[] = empty($event['host']) ? 'empty' : $event['host'];
            if (!empty($event['_file'])) {
                $file = $event['_file'];
                $exception = $event['_exception'];
                $line = $event['_line'];
            }
        }
        self::assertEquals([
            0 => [
                'version' => '1.1',
                'host' => $hosts[0],
                'short_message' => 'A debug message.',
                'level' => 7,
                'timestamp' => $timestamps[0],
            ],
            1 => [
                'version' => '1.1',
                'host' => $hosts[1],
                'short_message' => 'An alert message',
                'level' => 1,
                'timestamp' => $timestamps[1],
                '_structure' => [
                    'data' => [0, 1],
                ],
            ],
            2 => [
                'version' => '1.1',
                'host' => $hosts[2],
                'short_message' => 'Exception example',
                'level' => 0,
                'timestamp' => $timestamps[2],
                'full_message' => "Exception: Test exception (0)\n\n#0 {main}\n",
                '_exception' => $exception,
                '_line' => $line,
                '_file' => $file,
            ],
            3 => [
                'version' => '1.1',
                'host' => $hosts[3],
                'short_message' => 'Structured message',
                'level' => 1,
                'timestamp' => $timestamps[3],
                'full_message' => 'There was a foo in bar',
                '_foo' => 'bar',
                '_bar' => 'baz',
                '_barKochba' => 15,
            ],
            4 => [
                'version' => '1.1',
                'host' => $hosts[4],
                'short_message' => 'A warning message.',
                'level' => 4,
                'timestamp' => $timestamps[4],
                '_structure' => [
                    'with' => [
                        'several' => 'nested',
                        0 => 'levels',
                    ],
                ],
            ],
            5 => [
                'version' => '1.1',
                'host' => $hosts[5],
                'short_message' =>
                    file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Clients' . DIRECTORY_SEPARATOR . 'bacon.txt'),
                'level' => 6,
                'timestamp' => $timestamps[5],
            ],
            6 => [
                'version' => '1.0',
                'short_message' => 'No host',
                'level' => 7,
                'timestamp' => $timestamps[6],
            ],
            7 => [
                'version' => '1.0',
                'host' => $hosts[7],
                'short_message' => 'First message',
                'level' => 7,
                'timestamp' => $timestamps[7],
            ],
            8 => [
                'version' => '1.0',
                'host' => $hosts[8],
                'short_message' => 'Second message',
                'level' => 7,
                'timestamp' => $timestamps[8],
            ],
        ], $events);
    }
}
