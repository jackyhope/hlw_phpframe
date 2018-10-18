<?php
final class SlightPHP
{

    public static $appDir = ".";
    public static $xhprof_on = false;
    public static $script_start_time = '';
    public static $zone;
    public static $defaultZone = "zone";
    public static $page;
    public static $defaultPage = "page";
    public static $entry;
    public static $defaultEntry = "entry";
    public static $splitFlag = "/";
    public static $urlFormat = "-";
    public static $urlSuffix = ".html";
    public static $zoneAlias;

    public static function setZoneAlias($zone, $alias)
    {
        SlightPHP::$zoneAlias[$zone] = $alias;
        return true;
    }

    public static function setUrlFormat($value)
    {
        SlightPHP::$urlFormat = $value;
        return true;
    }

    public static function getUrlFormat() 
    {
        return SlightPHP::$urlFormat;
    }

    public static function setUrlSuffix($value) 
    {
        SlightPHP::$urlSuffix = $value;
        return true;
    }

    public static function getUrlSuffix()
    {
        return SlightPHP::$urlSuffix;
    }

    /**
     * @param string $zone
     * @return string | boolean
     */
    public static function getZoneAlias($zone)
    {
        return isset(SlightPHP::$zoneAlias[$zone]) ? SlightPHP::$zoneAlias[$zone] : false;
    }

    public static function setDefaultZone($zone) 
    {
        SlightPHP::$defaultZone = $zone;
        return true;
    }

    public static function setXhprofOn($zone)
    {
        SlightPHP::$xhprof_on = $zone;
        return true;
    }

    /**
     * set script time
     * 
     * @param string $zone
     * @return boolean
     */
    public static function setScriptTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        SlightPHP::$script_start_time = ((float) $usec + (float) $sec);
        return true;
    }

    /**
     * defaultZone get
     * 
     * @return string
     */
    public static function getDefaultZone() 
    {
        return SlightPHP::$defaultZone;
    }

    /**
     * defaultClass set
     * 
     * @param string $page
     * @return boolean
     */
    public static function setDefaultPage($page) 
    {
        SlightPHP::$defaultPage = $page;
        return true;
    }

    /**
     * getDefaultClass get
     * 
     * @return string
     */
    public static function getDefaultPage()
    {
        return SlightPHP::$defaultPage;
    }

    /**
     * defaultMethod set
     * 
     * @param string $entry
     * @return boolean
     */
    public static function setDefaultEntry($entry) 
    {
        SlightPHP::$defaultEntry = $entry;
        return true;
    }

    /**
     * defaultMethod get
     * 
     * @return string $method
     */
    public static function getDefaultEntry() 
    {
        return SlightPHP::$defaultEntry;
    }

    /**
     * splitFlag set
     * 
     * @param string $flag
     * @return boolean
     */
    public static function setSplitFlag($flag) 
    {
        SlightPHP::$splitFlag = $flag;
        return true;
    }

    /**
     * defaultMethod get
     * 
     * @return string
     */
    public static function getSplitFlag()
    {
        return SlightPHP::$splitFlag;
    }

    /**
     * appDir set && get
     * IMPORTANT: you must set absolute path if you use extension mode(extension=SlightPHP.so)
     *
     * @param string $dir
     * @return boolean
     */
    public static function setAppDir($dir)
    {
        SlightPHP::$appDir = $dir;
        return true;
    }

    /**
     * appDir get
     * 
     * @return string
     */
    public static function getAppDir()
    {
        return SlightPHP::$appDir;
    }

    /**
     * debug status set
     *
     * @param boolean $debug
     * @return boolean
     */
    public static function setDebug($debug) 
    {
        SlightPHP::$_debug = false;
        return true;
    }

    /**
     * debug status get
     * 
     * @return boolean 
     */
    public static function getDebug() 
    {
        return SlightPHP::$_debug;
    }

    final public static function run($path = "") 
    {
        //{{{
        $splitFlag = preg_quote(SlightPHP::$splitFlag, "/");
        $path_array = array();
        if (!empty($path)) {
            $isPart = true;
            $path_array = preg_split("/[$splitFlag\/]/", $path, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $isPart = false;
            if (!empty($_SERVER["PATH_INFO"]))
                $path_array = preg_split("/[$splitFlag\/]/", $_SERVER["PATH_INFO"], -1, PREG_SPLIT_NO_EMPTY);
        }
        $zone = !empty($path_array[0]) ? $path_array[0] : SlightPHP::$defaultZone;
        $page = !empty($path_array[1]) ? $path_array[1] : SlightPHP::$defaultPage;
        $entry = !empty($path_array[2]) ? $path_array[2] : SlightPHP::$defaultEntry;

        if (SlightPHP::$zoneAlias && ($key = array_search($zone, SlightPHP::$zoneAlias)) !== false) {
            $zone = $key;
        }
        if (!$isPart) {
            SlightPHP::$zone = $zone;
            SlightPHP::$page = $page;
            SlightPHP::$entry = $entry;
        } else {
            if ($zone == SlightPHP::$zone && $page == SlightPHP::$page && $entry == SlightPHP::$entry) {
                SlightPHP::debug("part ignored [$path]");
                return;
            }
        }

        $app_file = SlightPHP::$appDir . DIRECTORY_SEPARATOR . $zone . DIRECTORY_SEPARATOR . $page . ".page.php";

        if (!file_exists($app_file)) {
            SlightPHP::debug("file[$app_file] not exists");
            return false;
        } else {
            require_once(realpath($app_file));
        }
        $method = "Page" . $entry;
        $classname = $zone . "_" . $page;

        if (!class_exists($classname)) {
            SlightPHP::debug("class[$classname] not exists");
            return false;
        }

        $classInstance = new $classname;
        if (!method_exists($classInstance, $method)) {
            SlightPHP::debug("method[$method] not exists in class[$classname]");
            return false;
        }
        $path_array[0] = $zone;
        $path_array[1] = $page;
        $path_array[2] = $entry;
        return call_user_func(array(&$classInstance, $method), $path_array);
    }

    /**
     * @var boolean
     */
    public static $_debug = 0;

    /* private */

    private function debug($debugmsg)
    {
        if (SlightPHP::$_debug) {
            error_log($debugmsg);
            echo "<!--slightphp debug: " . $debugmsg . "-->";
        }
    }

}

?>
