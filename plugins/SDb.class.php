<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/DbData.php");
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/DbObject.php");

/**
 * @package SlightPHP
 */
class SDb {

    static $engines = array("mysql", "pdo_mysql", "pdo_new_mysql", "mssql", "pdo_mssql");
    static $_DbConfigFile;
    static $_DbdefaultZone = "default";
    static $_DbConfigCache;
    static $pool_server_connected;

    /**
     * @param string $engine enum("mysql");
     * @return DbObject
     */
    static function getDbEngine($engine) {
        $engine = strtolower($engine);
        if (!in_array($engine, SDb::$engines)) {
            return false;
        }
        if ($engine == "mysql" && extension_loaded("mysql")) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_Mysql.php");
            return new Db_Mysql;
        } elseif ($engine == "pdo_mysql") {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_PDO.php");
            return new Db_PDO("mysql");
        } elseif ($engine == "pdo_mssql") {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_PDO.php");
            return new Db_PDO("dblib");
        } elseif ($engine == "pdo_new_mysql") {
            $is_pool_connected = self::checkPoolServerByPort();
            if ($is_pool_connected) {
                require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_PDOPROXY.php");
                return new Db_PDOPROXY("mysql");
            } else {
                require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_PDONEW.php");
                return new Db_PDONEW("mysql");
            }
        } elseif ($engine == "mssql") {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "db/Db_Mssql.php");
            return new Db_Mssql();
        }
    }

    static function setConfigFile($file) {
        SDb::$_DbConfigFile = $file;
    }

    static function getConfigFile() {
        return SDb::$_DbConfigFile;
    }

    /**
     * @param string $zone
     * @param string $type	main|query
     * @return array
     */
    static function getConfig($zone, $type = "main") {
        if (!SDb::$_DbConfigFile) {
            return;
        }


        $cache = &SDb::$_DbConfigCache;
        if (isset($cache[$zone]) && isset($cache[$zone][$type])) {
            $i = array_rand($cache[$zone][$type]);
            return $cache[$zone][$type][$i];
        }

        $file_data = parse_ini_file(realpath(SDb::$_DbConfigFile), true,INI_SCANNER_RAW);
        if (isset($file_data[$zone])) {
            $db = $file_data[$zone];
        } elseif (isset($file_data[SDb::$_DbdefaultZone])) {
            $db = $file_data[SDb::$_DbdefaultZone];
        } else {
            return;
        }
        foreach ($db as $key => $row) {
            
        }

        //no query to direct to main
        $query_flag = false;
        foreach ($db as $key => $row) {
            if (strpos($key, "main") !== false) {
                $index = "main";
            } elseif (strpos($key, "query") !== false) {
                $index = "query";
            } else {
                continue;
            }
            $row = str_replace(":", "=", $row);
            $row = str_replace(",", "&", $row);
            parse_str($row, $out);
            if (!empty($out)) {
                if (strpos($key, "query") !== false)
                    $query_flag = true;
                $cache[$zone][$index][] = $out;
            }
        }
        if (!$query_flag) {
            $type = "main";
        }
        $i = array_rand($cache[$zone][$type]);
        return $cache[$zone][$type][$i];
    }

    private static function checkPoolServerByPort() {
        if (extension_loaded('connect_pool') && self::$pool_server_connected == true) {
            return true;
        }

        if (extension_loaded('connect_pool')) {
            $fp = @fsockopen('127.0.0.1', CP_DEFAULT_PDO_PORT, $err_no, $err_msg, 0.01);
            if ($fp) {
                self::$pool_server_connected = true;
                @fclose($fp);
            }

            return $fp ? true : false;
        } else {
            return false;
        }
    }

}
