<?php

namespace Sweeper\HelperPhp\DingTalk;

use InvalidArgumentException;
use Sweeper\GuzzleHttpRequest\Response;

/**
 * 钉钉通知服务
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/18 12:48
 * @Package \Sweeper\HelperPhp\DingTalk\DingTalkService
 * @example
 * $title   = '图搜资源用尽，请充值';
        $texts[] = '<font face="黑体" color=red size=5>====图搜资源用尽，请充值 - 测试数据====</font>';
        $prefix  = '> ';
        $texts[] = $prefix . '**时间**：' . date('Y-m-d H:i:s');
        $texts[] = $prefix . '**备注**：' . '[修改图搜资源为可用](http://192.168.10.100:1901/index/SysPara/Index)';
        $text    = implode('</br>' . PHP_EOL, $texts);

        return DingTalkService::instance()
                              ->setRobotSignSecret('xxx')
                              ->setRobotAccessToken('xxx')
                              ->sendNotifyToDingTalk('', false, DingTalkService::generateMarkdownData($title, $text, false, [16675112194]));
 */
class DingTalkService extends Request
{

    /**
     * 获取钉钉的部门列表
     * User: Sweeper
     * Time: 2023/1/10 16:50
     * @return Response
     */
    public function getDepartmentList(): Response
    {
        return $this->get($this->buildRequestUri('department/list', static::DING_TALK_URL), static::withQuery(['access_token' => $this->getAccessToken()]));
    }

    /**
     * 获取钉钉的部门用户列表
     * User: Sweeper
     * Time: 2023/1/10 16:50
     * @param $deptId
     * @return Response
     */
    public function getDepartmentUserList($deptId): Response
    {
        return $this->get($this->buildRequestUri('user/list', static::DING_TALK_URL), static::withQuery(['access_token' => $this->getAccessToken(), 'department_id' => $deptId]));
    }

    /**
     * 推送普通钉钉消息给销售
     * User: Sweeper
     * Time: 2023/1/10 16:51
     * @param int    $dingTalkUserId
     * @param string $text
     * @return Response
     */
    public function sendNotifyBySalesName(int $dingTalkUserId, string $text): Response
    {
        if (empty($dingTalkUserId)) {
            throw new InvalidArgumentException('未找到关联钉钉用户');
        }

        return $this->sendTextMessage($dingTalkUserId, $text);
    }

    /**
     * 给钉钉用户发送消息
     * User: Sweeper
     * Time: 2023/1/10 16:51
     * @param int    $toUserId
     * @param string $msgContent
     * @return Response
     */
    public function sendTextMessage(int $toUserId, string $msgContent): Response
    {
        $params = [
            'touser'  => $toUserId,
            'agentid' => $this->getAgentId(),
            'msgtype' => static::MSG_TYPE_TEXT,
            'text'    => ['content' => $msgContent],
        ];

        return $this->post($this->buildRequestUri('message/send?access_token=' . $this->getAccessToken(), static::DING_TALK_URL), static::withJson($params));
    }

    /**
     * 发送通知
     * User: Sweeper
     * Time: 2023/1/10 17:10
     * @param string $url
     * @param string $message
     * @param bool   $isAtAll
     * @param array  $messageData
     * @return Response
     */
    public function sendNotify(string $url, string $message, bool $isAtAll = false, array $messageData = []): Response
    {
        $defaultFormat = [
            'msgtype' => static::MSG_TYPE_TEXT,
            'text'    => ['content' => $message],
            "at"      => [
                "isAtAll" => $isAtAll,
            ],
        ];
        if (isset($messageData['msgtype'], static::MSG_TYPE_FORMAT[$messageData['msgtype']])) {
            $defaultFormat = static::MSG_TYPE_FORMAT[$messageData['msgtype']];
        }
        $messageData = array_replace($defaultFormat, $messageData);

        return $this->post($url, static::withJson($messageData));
    }

    /**
     * 发消息通知钉钉群
     * User: Sweeper
     * Time: 2023/1/10 17:06
     * @param string $message
     * @param bool   $isAtAll
     * @param array  $messageData
     * @return Response
     */
    public function sendNotifyToDingTalk(string $message, bool $isAtAll = false, array $messageData = []): Response
    {
        $secret      = $this->getRobotSignSecret() ?? ($this->getConfig('secret') ?: '');
        $accessToken = $this->getRobotAccessToken() ?? ($this->getConfig('access_token') ?: '');

        return $this->setRobotSignSecret($secret)->setRobotAccessToken($accessToken)->sendNotify($this->generateSignUrl('robot/send'), $message, $isAtAll, $messageData);
    }

    /**
     * 通过关键字发送通知
     * User: Sweeper
     * Time: 2023/1/10 14:52
     * @docUrl https://ding-doc.dingtalk.com/document/app/custom-robot-access
     * @sampleRequest {"msgtype": "text","text": {"content": "我就是我, @150XXXXXXXX 是不一样的烟火"},"at": {"atMobiles": ["150XXXXXXXX"],"isAtAll": false}}
     * @param string      $message
     * @param string|null $keyword
     * @param bool        $isAtAll
     * @param bool        $containKeyword
     * @return Response
     */
    public function sendNotifyByKeyword(string $message, string $keyword = null, bool $isAtAll = false, bool $containKeyword = true): Response
    {
        $messageData = [
            'msgtype' => static::MSG_TYPE_TEXT,
            'text'    => ['content' => $containKeyword ? "{$keyword}：{$message}" : $message],
            "at"      => [
                "isAtAll"   => $isAtAll,
                "atUserIds" => [],//被@人的用户userid。 注意 在content里添加@人的userid。
                "atMobiles" => [],//被@人的手机号。 注意 在content里添加@人的手机号，且只有在群内的成员才可被@，非群内成员手机号会被脱敏。
            ],
        ];
        $secret      = $this->getRobotSignSecret() ?? ($this->getConfig('secret') ?: '');
        $accessToken = $this->getRobotAccessToken() ?? ($this->getConfig('access_token') ?: '');

        return $this->setRobotSignSecret($secret)->setRobotAccessToken($accessToken)->sendNotify($this->generateSignUrl('robot/send'), $message, $isAtAll, $messageData);
    }
}