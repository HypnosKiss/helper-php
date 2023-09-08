<?php

namespace Sweeper\HelperPhp\DingTalk;

use Sweeper\DesignPattern\Traits\Multiton;

use function Sweeper\HelperPhp\get_microtime;
use function Sweeper\HelperPhp\str_to_utf8;

/**
 * 发送钉钉消息
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/5 19:23
 * @Path \Sweeper\HelperPhp\DingTalk\DingTalk
 */
class DingTalk
{

    use Multiton;

    protected $url    = 'https://oapi.dingtalk.com/robot/send?access_token=xxx';

    protected $secret = 'xxx';

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * 获取签名
     * User: Sweeper
     * Time: 2023/9/5 19:17
     * @return array
     */
    private function getSign(): array
    {
        $timestamp = get_microtime();
        $sign      = base64_encode(hash_hmac('sha256', $timestamp . "\n" . $this->getSecret(), $this->getSecret(), true));
        $sign      = str_to_utf8(urlencode($sign));

        return ['timestamp' => $timestamp, 'sign' => $sign];
    }

    /**
     * POST请求
     * User: Sweeper
     * Time: 2023/9/5 19:19
     * @param       $url
     * @param       $data
     * @param array $header
     * @return bool|string
     */
    protected static function curlPost($url, $data, array $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * 通过关键字发送通知
     * @param string $message
     * @param string $keyword
     * @param bool   $isAtAll
     * @return bool|string
     * @docUrl https://ding-doc.dingtalk.com/document/app/custom-robot-access
     * @sampleRequest {"msgtype": "text","text": {"content": "我就是我, @150XXXXXXXX 是不一样的烟火"},"at": {"atMobiles": ["150XXXXXXXX"],"isAtAll": false}}
     */
    public function sendNoticeByKeyword(string $message, string $keyword = '监控警告', bool $isAtAll = true)
    {
        $header = ['Content-Type: application/json;charset=utf-8'];
        $data   = [
            'msgtype' => 'text',
            'text'    => ['content' => "{$keyword}：{$message}"],
            "at"      => [
                // "atMobiles"=> [],//被@人的手机号。 注意 在content里添加@人的手机号。
                "isAtAll" => $isAtAll,
            ],
        ];

        return static::curlPost($this->getUrl(), json_encode($data), $header);
    }

    /**
     * 发送通知
     * @param string $url
     * @param string $message
     * @param bool   $isAtAll
     * @return bool|string
     */
    public function sendNotice(string $url, string $message, bool $isAtAll = true)
    {
        $header = ['Content-Type: application/json;charset=utf-8'];
        $data   = [
            'msgtype' => 'text',
            'text'    => ['content' => $message],
            "at"      => [
                // "atMobiles"=> [],//被@人的手机号。 注意 在content里添加@人的手机号。
                "isAtAll" => $isAtAll,
            ],
        ];

        return static::curlPost($url, json_encode($data), $header);
    }

    /**
     * 发消息通知钉钉群
     * @param string $message
     * @return bool|string
     */
    public function sendMsgToDingTalk(string $message)
    {
        $sign_info = $this->getSign();
        $url       = $this->getUrl() . "&timestamp=" . $sign_info['timestamp'] . "&sign=" . $sign_info['sign'];
        $data      = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);

        return static::curlPost($url, $data);
    }

}