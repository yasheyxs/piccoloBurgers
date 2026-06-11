<?php

$autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/helpers.php';
