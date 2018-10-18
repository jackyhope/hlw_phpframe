<?php

/**
 * HTTP CURL简单封装
 * 
 * @author leohu
 *
 */
class SCurl
{

    /**
     * create HTTP GET request
     * 
     * @param string $url
     * @param array $params
     * @return bool|string
     */
    public static function get($url, $params = array()) {
        return self::request($url, $params);
    }

    /**
     * create HTTP POST request
     * 
     * @param string $url
     * @param array $params
     * @return bool|string
     */
    public static function post($url, $params = array()) {
        return self::request($url, $params, array(CURLOPT_POST => TRUE));
    }

    /**
     * create HTTP request whit customize options
     * 
     * @param string $url
     * @param array $params
     * @param array $option
     * @return boolean|mixed
     */
    public static function request($url, $params = array(), $option = array()) {

        $ch = curl_init();
        $option += array(
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => TRUE,
        );
        if (stripos($url, "https://") !== FALSE) {
            $option += array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            );
        }
        if (isset($option[CURLOPT_POST])) {
            $option += array(
                CURLOPT_POSTFIELDS => http_build_query($params)
            );
        } else {
            if ($params) {
                $url .= strpos($url, '?') === FALSE ? '?' : '&';
                $url .= http_build_query($params);
            }
        }
        $option[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $option);

        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            self::$error = $error;
            return FALSE;
        }

        return $resp;
    }

    private static $error = '';

    /**
     * return error message
     * 
     * @return string
     */
    public static function getError() {
        return self::$error;
    }

}
