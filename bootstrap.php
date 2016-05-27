<?php

// Define path to application directory
define('ROOT_PATH', __DIR__);

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

require_once ROOT_PATH . '/vendor/autoload.php';
