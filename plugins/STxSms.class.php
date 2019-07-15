<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 腾讯云短信SDK
 * User: SOSO
 * Date: 2019/7/15
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */
include_once('sms/tencent/src/index.php');

use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
use Qcloud\Sms\SmsVoiceVerifyCodeSender;
use Qcloud\Sms\SmsVoicePromptSender;
use Qcloud\Sms\SmsStatusPuller;
use Qcloud\Sms\SmsMobileStatusPuller;

use Qcloud\Sms\VoiceFileUploader;
use Qcloud\Sms\FileVoiceSender;
use Qcloud\Sms\TtsVoiceSender;

class STxSms
{
    protected $appId;
    protected $appKey = '';
    protected $templateId;
    protected $smsSign;
    protected $nationCode = 86;
    protected $message = '';

    public function __construct($config = '') {
        isset($config['appId']) && $this->appId = $config['appId'];
        isset($config['appKey']) && $this->appKey = $config['appKey'];
        isset($config['templateId']) && $this->templateId = $config['templateId'];
        isset($config['smsSign']) && $this->smsSign = $config['smsSign'];
        isset($config['nationCode']) && $this->nationCode = $config['nationCode'];
    }

    /**
     * @desc  错误消息获取
     * @return string
     */
    public function getError() {
        return $this->message;
    }

    /**
     * @desc 单条短信发送
     * @param $phone
     * @param $content
     * @return bool
     */
    public function sentOne($phone, $content) {
        try {
            $sender = new SmsSingleSender($this->appId, $this->appKey);
            $result = $sender->send(0, $this->nationCode, $phone, $content);
            $rsp = json_decode($result);
            $this->message = $rsp['errmsg'];
            if ($rsp['result'] == 0) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 指定模板ID单发短信
     * @param $phone
     * @param $params
     * @return bool
     */
    public function sentTemOne($phone, $params) {
        try {
            $sender = new SmsSingleSender($this->appId, $this->appKey);
            $result = $sender->sendWithParam($this->nationCode, $phone, $this->templateId, $params, $this->smsSign);
            $rsp = json_decode($result);
            $this->message = $rsp['errmsg'];
            if ($rsp['result'] == 0) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc  短信群发
     * @param $phoneNumbers
     * @param $content
     * @return bool
     */
    public function multiSent($phoneNumbers, $content) {
        try {
            $sender = new SmsMultiSender($this->appId, $this->appKey);
            $result = $sender->send(0, $this->nationCode, $phoneNumbers, $content);
            $rsp = json_decode($result);
            $this->message = $rsp['errmsg'];
            if ($rsp['result'] == 0) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc  模板信息群发
     * @param $phoneNumbers
     * @param $templateId
     * @param $params
     * @return bool
     */
    public function multiTempSent($phoneNumbers, $templateId, $params) {
        // 指定模板ID群发
        try {
            $sender = new SmsMultiSender($this->appId, $this->appKey);
            $result = $sender->sendWithParam($this->nationCode, $phoneNumbers, $templateId, $params, $this->smsSign);
            $rsp = json_decode($result);
            if ($rsp['result'] == 0) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

}