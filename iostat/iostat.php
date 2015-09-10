#!/usr/bin/php
<?php

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Tools.php');

$interval = isset($argv[1]) ? intval($argv[1]) : 1;

$f = popen('/usr/bin/iostat -xt ' . $interval, 'r');
$cols = [];
$datetime = 0;
$state = 0; // 1 - cpu, 2 - io
while ($line = fgets($f)) {
    //echo $line;
    if ($state === 1) {
        if ($values = Tools::parseValuesLine($line, $cols)) {
            Tools::writeValues('cpu', $values, $datetime);
        } else {
            $state = 0;
        }
    } else if ($state === 2) {
        if ($values = Tools::parseValuesLine($line, $cols)) {
            $what = array_shift($values);
            Tools::writeValues($what, $values, $datetime);
        } else {
            $state = 0;
        }
    } else {
        if (preg_match('/(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2})/', $line, $m)) {
            $datetime = $m[1];
            //echo 'Datetime found: ' . $datetime . PHP_EOL;
        } else if (preg_match('/avg-cpu:((\s+%(\w+))+)/', $line, $m)) {
            if ($cols = Tools::parseHeadersLine($m[1])) {
                //echo 'Cpu stats found: ' . PHP_EOL;
                $state = 1;
            }
        } else if (preg_match('/(Device:.*)/', $line, $m)) {
            if ($cols = Tools::parseHeadersLine($m[1])) {
                //echo 'Devices stats found: ' . PHP_EOL;
                $state = 2;
            }
        }
    }
}
pclose($f);
