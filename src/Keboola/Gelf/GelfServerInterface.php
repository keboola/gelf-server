<?php

namespace Keboola\Gelf;

interface GelfServerInterface
{
    /**
     * @param int $minPort
     * @param int $maxPort
     * @param callable $onStart
     * @param callable $onProcess
     * @param callable $onTerminate
     * @param callable $onEvent
     * @return void
     */
    public function start($minPort, $maxPort, $onStart, $onProcess, $onTerminate, $onEvent);
    
    
}