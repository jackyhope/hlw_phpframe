<?php

define('SLIGHT_PHP_ROOT', __DIR__);

class_exists('SlightPHP', false) || require __DIR__ . '/SlightPHP.php';

class SlightLoader {

    public static function loadClass($className)
    {
        $className = ltrim($className, '\\');

        if ($className{0} == 'S') {
            $path = SLIGHT_PHP_ROOT . '/plugins/' . self::toPath($className);
            if (self::loadFile($path)) {
                return true;
            }
        }

        if ('hlw_' == substr($className, 0, 4)) {
            $classNameNoPrefix = substr($className, 4);
            $path = SLIGHT_PHP_ROOT . '/hlwcore/' . self::toPath($classNameNoPrefix);
            if (self::loadFile($path)) {
                return true;
            }
        }

        $path = SlightPHP::$appDir . '/' . self::toPath($className);
        if (self::loadFile($path)) {
            return true;
        }

        return false;
    }

    public static function toPath($className)
    {
        return str_replace(array('_', '\\'), '/', $className) . '.class.php';
    }

    public static function loadFile($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        require_once $path;

        return true;
    }

}

spl_autoload_register(array('SlightLoader', 'loadClass'));

