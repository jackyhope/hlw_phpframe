<?php

class hlw_lib_ApiClient
{

    private static $appid;
    private static $secret;
    private static $timeout;

    public static function init($appid, $secret, $timeout = 30, $sdk_lib = array('hlwApiSdk'))
    {
        self::$appid = $appid;
        self::$secret = $secret;
        self::$timeout = $timeout;
        hlw_lib_ApiClient_Loader::init($sdk_lib);
    }

    /**
     * build api client instance
     *
     * @param mixed $instance
     * @param array $map
     * @throws Exception
     */
    public static function build(&$instance, array $map = array()) 
    {
        $class = get_class($instance);
        if ($class === FALSE) {
            throw new Exception('', -1);
        }
        if ($class) {
            $class = str_replace('.', '\\', $class);
        }

        if ($map == NULL) {
            $map = hlw_lib_ApiClient_Loader::loadConfig($class);
        }

        $instance = new hlw_lib_ApiClient_Proxy(self::$appid, self::$secret, self::$timeout, $class, $map);
    }

}
