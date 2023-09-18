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
 */
class DingTalkService extends Request
{

    /** @var string[] 机器人配置映射 */
    public const ROBOT_MAP = [
        '机器人' => [
            'secret'       => '',
            'access_token' => '',
        ],
    ];

    /**
     * 获取钉钉的部门列表
     * User: Sweeper
     * Time: 2023/1/10 16:50
     * @return Response
     */
    public function getDepartmentList(): Response
    {
        return $this->get($this->buildRequestUri('department/list'), ['access_token' => $this->getAccessToken()]);
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
        return $this->get($this->buildRequestUri('user/list'), ['access_token' => $this->getAccessToken(), 'department_id' => $deptId]);
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

        return $this->post($this->buildRequestUri('message/send?access_token=' . $this->getAccessToken()), $params);
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

        return $this->post($url, $messageData);
    }

    /**
     * 发消息通知钉钉群
     * User: Sweeper
     * Time: 2023/1/10 17:06
     * @param string      $message
     * @param string|null $robot
     * @param bool        $isAtAll
     * @param array       $messageData
     * @return Response
     */
    public function sendNotifyToDingTalk(string $message, string $robot = null, bool $isAtAll = false, array $messageData = []): Response
    {
        $config      = static::ROBOT_MAP[$robot] ?? [];
        $secret      = $this->getRobotSecret() ?? ($this->getConfig('secret') ?: $config['secret'] ?? '');
        $accessToken = $this->getRobotAccessToken() ?? ($this->getConfig('access_token') ?: $config['access_token'] ?? '');

        return $this->setRobotSecret($secret)->setRobotAccessToken($accessToken)->sendNotify($this->generateSignUrl('robot/send'), $message, $isAtAll, $messageData);
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
     * @param string|null $robot
     * @param bool        $containKeyword
     * @return Response
     */
    public function sendNotifyByKeyword(string $message, string $keyword = null, bool $isAtAll = false, string $robot = null, bool $containKeyword = true): Response
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
        $config      = static::ROBOT_MAP[$robot] ?? [];
        $secret      = $this->getRobotSecret() ?? ($this->getConfig('secret') ?: $config['secret'] ?? '');
        $accessToken = $this->getRobotAccessToken() ?? ($this->getConfig('access_token') ?: $config['access_token'] ?? '');

        return $this->setRobotSecret($secret)->setRobotAccessToken($accessToken)->sendNotify($this->generateSignUrl('robot/send'), $message, $isAtAll, $messageData);
    }

}
