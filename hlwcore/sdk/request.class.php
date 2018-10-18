<?php

class hlw_sdk_request
{
    //当前app的appid
    protected $appid;
    //当前app的appsecret
    protected $appsecret;
    //当前的app的详细信息
    protected $appinfo;
    //post 数据
    protected $_receive;
    //错误内容
    private $_errorMsg;

    public function __construct()
    {
        $this->init();
    }

    /**
     * api接口初始化，进行常规检查
     */
    private function init()
    {
        $this->getRev();
        $this->appid = $this->_receive['appid'];
        $this->checkAppId();
        $this->checkSign();
        $this->beforeApi();
    }

    /**
     * 解析来自外部提交的数据
     */
    public function getRev()
    {
        if ($this->_receive)
            return $this;
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)) {
            $this->_receive = json_decode($postStr, true);
        }
        return $this;
    }

    /**
     * 抛出错误消息
     *
     */
    public function apiError($code, $msg) 
    {
        if ($this->_receive['debug'] == 1) {
            $msg = $this->getError() ? $this->getError() : $msg;
        }
        hlw_lib_BaseUtils::json(array(), $code, $this->_receive['nonce'], $msg);
    }

    /**
     * 返回数据
     * @param array $data
     */
    public function apiSuccess($data) {
        hlw_lib_BaseUtils::json($data, 0, $this->_receive['nonce']);
    }

    /**
     * 设置错误信息
     */
    public function setError($msg) {
        $this->_errorMsg = $msg;
    }

    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->_errorMsg;
    }

    /**
     * 验证sign
     * sign加密算法
     */
    private function checkSign() {
        $signature = $this->_receive['signature'];
        $tmpArr = $this->_receive;
        unset($tmpArr['signature']);
        $tmpArr['appsecret'] = $this->appsecret;
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));
        if ($tmpStr !== $signature) {
            $this->apiError(10003, "sign error");
        }
    }

    /**
     * API执行前的验证，包括当前app对此api是否有权限
     */
    private function beforeApi() {
        $page = SlightPHP::$page;
        $entry = SlightPHP::$entry;
        $splitFlag = SlightPHP::$splitFlag;
        $allow_api = trim($this->appinfo['allow_api']);
        $allow_api = $this->dealApiConfigRule($allow_api);
        $current_api = strtolower($this->_receive['appname'] . "/{$page}/{$entry}");
        $this->checkAllowApi($current_api, $allow_api);
    }

    private function dealApiConfigRule($config) {
        if (empty($config) || is_null($config)) {
            $config = array();
        } else {
            $config = explode(';', $config);
        }
        return $config;
    }

    private function checkAllowApi($current_api, $allow_api) {
        if (!empty($allow_api)) {
            $allow = false;
            foreach ($allow_api as $_da) {
                if (empty($_da) || is_null($_da))
                    continue;
                $_da = str_replace(array('*', '/'), array('.*', '\/'), $_da);
                $pattern = "/^{$_da}$/";
                if (preg_match($pattern, $current_api)) {
                    $allow = true;
                }
            }
            if (false === $allow) {
                $this->apiError(10004, '未找到正确API');
            }
        }
    }

}
