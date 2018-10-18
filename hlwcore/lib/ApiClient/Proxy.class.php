<?php

class hlw_lib_ApiClient_Proxy 
{

    private $_appid;
    private $_secret;
    private $_class;
    private $_timeout;
    private $_map;

    /**
     * @param string $appid
     * @param string $secret
     * @param float $timeout
     * @param string $class
     * @param array $map
     */
    public function __construct($appid, $secret, $timeout, $class, array $map) {
        $this->_appid = $appid;
        $this->_secret = $secret;
        $this->_timeout = $timeout;
        $this->_class = $class;
        $this->_map = $map;
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $timeout
     * @param string $headers
     * @param string $uri
     * @param string $clientClass
     * @param string $clientMethod
     * @param array $arguments
     * @throws Exception
     * @return mixed
     */
    private function request($host, $port, $timeout = 0, array $headers = array(), $uri, $clientClass, $clientMethod, array $arguments) 
    {

        $socket = new hlw_lib_ApiClient_HttpClient($host, $port, $uri);
        $transport = new Thrift\Transport\TBufferedTransport($socket, 1024, 1024);
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new $clientClass($protocol);

        if ($timeout > 0) {
            $socket->setTimeoutSecs($timeout);
        }

        if ($headers) {
            $socket->addHeaders($headers);
        }

        $transport->open();
        try {
            $result = call_user_func_array(array($client, $clientMethod), $arguments);
        } catch (Exception $e) {
            // catch unknown result
            $message = $e->getMessage();
            if (strlen($message) > 14 && substr($message, -14) == 'unknown result') {
                $result = NULL;
            } else {
                SLogger::getLogger("unknown_thrift_service", "thrift_error")->error("thrift call error", $e);
                throw $e;
            }
        }
        $transport->close();

        return $result;
    }

    /**
     * @param string $appid
     * @param string $secret
     * @param string $timestamp
     * @return string
     */
    private function createSignature($appid, $secret, $timestamp)
    {
        return md5($appid . $secret . $timestamp);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @throws Exception
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $cfg = array();
        foreach ($this->_map as $url => $methods) {
            if (in_array($method, $methods)) {
                $info = parse_url($url);
                $cfg = array(
                    $info['host'],
                    isset($info['port']) ? $info['port'] : '80',
                    isset($info['path']) ? $info['path'] : '/');
            }
        }
        if ($cfg == NULL) {
            $methodException = new Exception('api 不存在', -1);
            SLogger::getLogger("unknown_thrift_service", "thrift_error")->error(" api 不存在", $methodException);
            throw $methodException;
        }

        list($host, $port, $uri) = $cfg;

        $timestamp = time();
        $sign = $this->createSignature(
                $this->_appid, $this->_secret, $timestamp);
        $headers = array(
            'HLW_APPID' => $this->_appid,
            'HLW_SIGNATURE' => $sign,
            'HLW_TIMESTAMP' => $timestamp,
            'HLW_SEQ' => uniqid());

        return $this->request($host, $port, $this->_timeout, $headers, $uri, $this->_class, $method, $arguments);
    }

}
