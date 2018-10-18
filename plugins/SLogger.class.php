<?php

$errorLevel = ini_get('error_reporting');
ini_set('error_reporting', $errorLevel ^ E_USER_WARNING ^ E_WARNING);

class_exists('Logger') || require __DIR__ . '/log4php/Logger.php';

ini_set('error_reporting', $errorLevel);

class SLogger
{
    const LOGGER_STORAGE_ROOT = '/var/log/gandianli';
    const LOGGER_DEFAULT_NAME = 'noname';
    const LOGGER_NAME_PATTERN = '/^[a-zA-Z0-9\-_.]{1,30}$/';
    const LOGGER_DEFAULT_DIR = 'cli';
    const LOGGER_DIR_PATTERN = '/^[a-zA-Z0-9\-_.]{1,30}$/';

    private static $loggerInstance = array();
    private static $loggerConfigTemplate = array(
        'level' => 'info',
        'appenders' => array()
    );
    private static $appenderConfigTemplate = array(
        'class' => 'LoggerAppenderDailyFile',
        'layout' => array(
            'class' => 'LoggerLayoutPattern',
            'params' => array(
                'conversionPattern' => "%d{Y-m-d H:i:s} [%p] %m%n%ex"
            )
        ),
        'params' => array(
            'datePattern' => 'Ymd',
            'file' => ''
        ),
        'threshold' => 'info'
    );

    public static function getLogger($loggerName = '', $storageDir = '')
    {
        $loggerName = self::detectLoggerName($loggerName);
        $storageDir = self::detectLoggerDir($storageDir);

        $name = "{$storageDir}-{$loggerName}";

        if (!isset(self::$loggerInstance[$name])) {
            self::$loggerInstance[$name] = self::buildLogger($storageDir, $loggerName);
        }

        return self::$loggerInstance[$name];
    }



    private static function detectLoggerName($name)
    {
        if (preg_match(self::LOGGER_NAME_PATTERN, $name)) {
            return $name;
        }

        return self::LOGGER_DEFAULT_NAME;
    }


    private static function detectLoggerDir($dir)
    {
        if (preg_match(self::LOGGER_DIR_PATTERN, $dir)) {
            return $dir;
        }

        $target = '';
        if (PHP_SAPI == 'cli') {
            $script = str_replace('/', '.', $GLOBALS['argv'][0]);
            $target = "cli/{$script}";
        } else {
            $target = str_replace(':', '_', $_SERVER['HTTP_HOST']);
        }

        return $target ?: self::LOGGER_DEFAULT_DIR;
    }


    private static function buildLogger($storageDir, $loggerName)
    {
        $name = "{$storageDir}-{$loggerName}";

        $loggerConfig = self::$loggerConfigTemplate;
        $loggerConfig['appenders'][] = $name;

        $appenderConfig = self::$appenderConfigTemplate;
        $appenderConfig['params']['file'] = sprintf('%s/%s/%s/%%s.log', self::LOGGER_STORAGE_ROOT, $storageDir, $loggerName);

        $config = array();
        $config['loggers'][$name] = $loggerConfig;
        $config['appenders'][$name] = $appenderConfig;

        \Logger::configure($config);

        return \Logger::getLogger($name);
    }

}
