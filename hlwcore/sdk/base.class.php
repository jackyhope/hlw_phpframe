<?php

class hlw_sdk_base 
{

    private $appid;
    private $appsecret;
    private $signature;
    private $_timeout = 3;
    public $_time;

    protected $appname = '';

    function __construct($appname) {
        $this->appname = $appname;
    }

    function init($appid, $appsecret) {
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        $this->_time = time();
        return $this;
    }

    function setTimeOut($t = 3) {
        $this->_timeout = (int) $t;
    }

    protected function doPost($uri, $data) {
        $data['appid'] = $this->appid;
        $data['timestamp'] = $this->_time;
        $data['nonce'] = $data['nonce'] ? $data['nonce'] : uniqid();
        $data['appname'] = $this->appname;
        $data['signature'] = $this->bulidSign($data);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        return curl_exec($ch);
    }

    private function bulidSign($data) {
        $data['appsecret'] = $this->appsecret;
        sort($data, SORT_STRING);
        $this->signature = sha1(implode($data));
        return $this->signature;
    }

}
