#!/usr/bin/php
<?php

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Tools.php');

if ($argc < 3) {
    echo 'Use with <what> and <name> parameters' . PHP_EOL;
    exit;
}
$what = $argv[1];
$name = $argv[2];

echo Tools::getValue($what, $name, true) . PHP_EOL;
