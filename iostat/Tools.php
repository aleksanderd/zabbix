<?php

class Tools {

    const MAX_FILE_SIZE = 777777;

    public static function superTrim($string)
    {
        $result = str_replace(
            [',', '/', '-', '%', ':'],
            ['.', '_', '_',  '',  ''],
            strtolower(trim($string))
        );
        return strlen($result) > 0 ? $result : false;
    }

    public static function parseHeadersLine($line)
    {
        $cols = [];
        foreach (explode(' ', $line) as $v) {
            if ($name = static::superTrim($v)) {
                $cols[$name] = 0;
            }
        }
        return count($cols) ? $cols : false;
    }

    /**
     * @param $line
     * @param $cols
     * @return bool|array
     */
    public static function parseValuesLine($line, $cols)
    {
        $t = explode(' ', $line);
        reset($cols);
        foreach ($t as $v) {
            if (!($v = static::superTrim($v))) {
                continue;
            }
            if (list($name, $val) = each($cols)) {
                $cols[$name] = $v;
            } else {
//            echo '! Too many values in line:' . $line . PHP_EOL;
                return false;
            }
        }
        if (each($cols)) {
//        echo '! Not enough values in line:' . $line . PHP_EOL;
            return false;
        }
        return $cols;
    }

    public static function formatValue($value, $datetime = null)
    {
        if ($datetime === null) {
            $datetime = date('d.m.Y H:i:s');
        }
        return $datetime .'|'. $value;
    }

    public static function getFilename($what, $name)
    {
        $folder = '/tmp/iostat';
        foreach ([$folder, $folder .'/'. $what] as $f) {
            if (!is_dir($f)) {
                if (!mkdir($f)) {
                    throw new Exception('Can not create folder: ' . $f);
                }
            }
        }
        return $folder .'/'. $what .'/'. $name;
    }

    public static function writeValue($what, $name, $value, $datetime = null)
    {
        $filename = static::getFilename($what, $name);
        if (is_readable($filename) && filesize($filename) > static::MAX_FILE_SIZE) {
            if (!unlink($filename)) {
                throw new Exception('Can not delete file: ' . $filename);
            }
        }
        if (!($f = fopen($filename, 'a'))) {
            throw new Exception('Can not open file: ' . $filename);
        }
        flock($f, LOCK_EX);
        fwrite($f, static::formatValue($value, $datetime) . PHP_EOL);
        fflush($f);
        flock($f, LOCK_UN);
        fclose($f);
    }

    public static function writeValues($what, $values, $datetime = null)
    {
        foreach ($values as $name => $value) {
            static::writeValue($what, $name, $value, $datetime);
        }
    }

    public static function readValuesAvg($what, $name, $delete = false)
    {
        $filename = Tools::getFilename($what, $name);
        if (is_readable($filename)) {
            if (!($f = fopen($filename, 'r'))) {
                return false;
            }
            flock($f, LOCK_SH);
            $valuesCount = 0;
            $valuesSum = 0;
            while ($line = fgets($f)) {
                $m = explode('|', $line);
                if (count($m) < 2) {
                    continue;
                }
                $valuesCount++;
                $valuesSum += floatval(trim($m[1]));
            }
            flock($f, LOCK_UN);
            fclose($f);
            if ($delete) {
                unlink($filename);
            }
            return $valuesSum / $valuesCount;
        } else {
            return false;
        }
    }

    public static function getValue($what, $name, $delete = false)
    {
        $last = $name . '._last_';
        if (($value = static::readValuesAvg($what, $name, $delete)) === false) {
            return static::readValuesAvg($what, $last);
        } else {
            if (is_file($filename = static::getFilename($what, $last))) {
                unlink($filename);
            }
            static::writeValue($what, $last, $value);
            return $value;
        }
    }

}

