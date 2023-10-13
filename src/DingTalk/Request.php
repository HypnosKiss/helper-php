<?php

namespace Sweeper\HelperPhp\DingTalk;

use Psr\Http\Message\ResponseInterface;
use Sweeper\GuzzleHttpRequest\HttpCode;
use Sweeper\GuzzleHttpRequest\Response;
use Sweeper\GuzzleHttpRequest\ServiceRequest;
use Sweeper\HelperPhp\Traits\RedisCache;

use function Sweeper\HelperPhp\get_microtime;
use function Sweeper\HelperPhp\str_to_utf8;

/**
 * 钉钉请求类
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/18 12:48
 * @Package \Sweeper\HelperPhp\DingTalk\Request
 */
class Request extends ServiceRequest
{

    use RedisCache;

    /** @var string 钉钉OApi的url */
    public const DING_TALK_URL        = "https://oapi.dingtalk.com";

    public const MSG_TYPE_TEXT        = "text";

    public const MSG_TYPE_LINK        = "link";

    public const MSG_TYPE_MARKDOWN    = "markdown";

    public const MSG_TYPE_ACTION_CARD = "actionCard";

    public const MSG_TYPE_FEED_CARD   = "feedCard";

    /** @var string 整体跳转ActionCard类型 */
    public const  ACTION_CARD_WHOLE = 'whole';

    /** @var string 独立跳转ActionCard类型 */
    public const  ACTION_CARD_INDEPENDENT = 'independent';

    /** @var string 0：按钮竖直排列 */
    public const  BTN_ORIENTATION_VERTICAL = '0';

    /** @var string 1：按钮横向排列 */
    public const  BTN_ORIENTATION_TRANSVERSE = '1';

    /** @var array 各类型对应的格式 */
    public const MSG_TYPE_FORMAT = [
        self::MSG_TYPE_TEXT        => [
            'msgtype' => self::MSG_TYPE_TEXT,// 消息类型，String 必填，此时固定为：text。
            'text'    => [
                'content' => '',// 消息内容。 String 必填
            ],
            'at'      => [
                'atMobiles' => [],// 被@人的手机号。 Array 注意 在content里添加@人的手机号，且只有在群内的成员才可被@，非群内成员手机号会被脱敏。
                'atUserIds' => [],// 被@人的用户userid。Array 注意 在content里添加@人的userid。
                'isAtAll'   => false,// 是否@所有人。Boolean
            ],
        ],
        self::MSG_TYPE_LINK        => [
            'msgtype' => self::MSG_TYPE_LINK,// 消息类型，String 必填，此时固定为：link。
            'link'    => [
                'title'      => '',// 消息标题。String 必填
                'text'       => '',// 消息内容。如果太长只会部分展示。 String 必填
                'messageUrl' => '',// 点击消息跳转的URL， String 必填
                'picUrl'     => '',// 图片URL。String
            ],
        ],
        self::MSG_TYPE_MARKDOWN    => [
            'msgtype'  => self::MSG_TYPE_MARKDOWN,// 消息类型，String 必填，此时固定为：markdown。
            'markdown' => [
                'title' => '',// 首屏会话透出的展示内容。 String 必填
                'text'  => '',// markdown格式的消息。 String 必填
            ],
            'at'       => [
                'atMobiles' => [],// 被@人的手机号。 Array 注意 在content里添加@人的手机号，且只有在群内的成员才可被@，非群内成员手机号会被脱敏。
                'atUserIds' => [],// 被@人的用户userid。Array 注意 在content里添加@人的userid。
                'isAtAll'   => false,// 是否@所有人。Boolean
            ],
        ],
        self::MSG_TYPE_ACTION_CARD => [
            // 整体跳转ActionCard类型
            self::ACTION_CARD_WHOLE       => [
                'msgtype'    => self::MSG_TYPE_ACTION_CARD,// 消息类型，String 必填，此时固定为：actionCard。
                'actionCard' => [
                    'title'          => '',// 首屏会话透出的展示内容。String 必填
                    'text'           => '',// markdown格式的消息。 String 必填
                    'singleTitle'    => '',// 单个按钮的标题。String 必填 注意 设置此项和singleURL后，btns无效。
                    'singleURL'      => '',// 点击消息跳转的URL。String 必填
                    'btnOrientation' => '',// 0：按钮竖直排列、1：按钮横向排列
                ],
            ],
            // 独立跳转ActionCard类型
            self::ACTION_CARD_INDEPENDENT => [
                'msgtype'    => self::MSG_TYPE_ACTION_CARD,// 消息类型，String 必填，此时固定为：actionCard。
                'actionCard' => [
                    'title'          => '',// 首屏会话透出的展示内容。String 必填
                    'text'           => '',// markdown格式的消息。 String 必填
                    'btns'           => [
                        [
                            'title'     => '',// 按钮标题。String 必填
                            'actionURL' => '',// 点击按钮触发的URL。String 必填
                        ],
                    ],
                    'btnOrientation' => '',// 0：按钮竖直排列、1：按钮横向排列
                ],
            ],
        ],
        self::MSG_TYPE_FEED_CARD   => [
            'msgtype'  => self::MSG_TYPE_FEED_CARD,// 消息类型，String 必填，此时固定为：feedCard。
            'feedCard' => [
                'links' => [
                    [
                        'title'      => '',// 单条信息文本。String 必填
                        'messageURL' => '',// 点击单条信息到跳转链接。String 必填
                        'picURL'     => '',// 单条信息后面图片的URL。
                    ],
                ],
            ],
        ],
    ];

    /** @var string 获取 token 路径 */
    public const GET_TOKEN_PATH = 'gettoken';

    /** @var string 钉钉 token 缓存前缀 */
    public const DINGTALK_TOKEN_PREFIX = 'dingtalk:token';

    /** @var string 钉钉 access_token */
    private $accessToken;

    /** @var int accessToken 过期时间 */
    private $accessTokenExpiresIn = 7200;

    /** @var string 应用的APPId */
    private $agentId = 'xxx';

    /** @var string 钉钉给的appkey */
    private $appKey = 'xxx';

    /** @var string 钉钉给的appSecret */
    private $appSecret = 'xxx';

    /** @var string 机器人密钥 */
    protected $robotSecret;

    /** @var string 机器人 accessToken */
    protected $robotAccessToken;

    /**
     * 获取 API 服务URL
     * User: Sweeper
     * Time: 2023/1/10 10:37
     * @return string
     */
    protected function getServerDomain(): string
    {
        return static::DING_TALK_URL;
    }

    /**
     * @return string
     */
    public function getAgentId(): string
    {
        return $this->agentId;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 11:09
     * @param string $agentId
     * @return $this
     */
    public function setAgentId(string $agentId): self
    {
        $this->agentId = $agentId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppKey(): string
    {
        return $this->appKey;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 11:09
     * @param string $appKey
     * @return static
     */
    public function setAppKey(string $appKey): self
    {
        $this->appKey = $appKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 11:08
     * @param string $appSecret
     * @return static
     */
    public function setAppSecret(string $appSecret): self
    {
        $this->appSecret = $appSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getRobotSecret(): ?string
    {
        return $this->robotSecret;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 15:53
     * @param string $robotSecret
     * @return $this
     */
    public function setRobotSecret(string $robotSecret): self
    {
        $this->robotSecret = $robotSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getRobotAccessToken(): ?string
    {
        return $this->robotAccessToken;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 16:47
     * @param string $robotAccessToken
     * @return $this
     */
    public function setRobotAccessToken(string $robotAccessToken): self
    {
        $this->robotAccessToken = $robotAccessToken;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccessTokenExpiresIn(): int
    {
        return $this->accessTokenExpiresIn;
    }

    /**
     * User: Sweeper
     * Time: 2023/1/10 12:44
     * @param int $accessTokenExpiresIn
     * @return $this
     */
    public function setAccessTokenExpiresIn(int $accessTokenExpiresIn): self
    {
        $this->accessTokenExpiresIn = $accessTokenExpiresIn;

        return $this;
    }

    /**
     * 请求钉钉 accessToken
     * User: Sweeper
     * Time: 2023/1/10 11:22
     * @param string|null $appKey
     * @param string|null $appSecret
     * @return string|void|null
     */
    private function fetchAccessToken(string $appKey = null, string $appSecret = null)
    {
        $appKey    = $appKey ?? $this->getAppKey();
        $appSecret = $appSecret ?? $this->getAppSecret();

        return $this->get($this->buildRequestUri(static::GET_TOKEN_PATH), ['appkey' => $appKey, 'appsecret' => $appSecret])->getSuccessResponse();
    }

    /**
     * 获取 access_token
     * User: Sweeper
     * Time: 2023/1/10 11:18
     * @return string
     */
    public function getAccessToken(): string
    {
        [$accessToken, $errors] = $this->getDataByHGet(static::DINGTALK_TOKEN_PREFIX, $this->getAgentId(), function() {
            $response = $this->fetchAccessToken();
            // $expiresIn   = $response['expires_in'] ?? 0;
            // if ($response['errcode'] === 0) {
            //     $this->setAccessToken($accessToken, $expiresIn);
            // }

            return $response['access_token'] ?? '';
        }, 5, $this->getAccessTokenExpiresIn() - 60);

        return $this->accessToken = $accessToken;
    }

    /**
     * 设置 access_token
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/18 14:59
     * @param string $accessToken
     * @param int    $expiresIn
     * @return $this
     */
    public function setAccessToken(string $accessToken, int $expiresIn = 0): self
    {
        $expiresIn         = $expiresIn ?: $this->getAccessTokenExpiresIn();
        $this->accessToken = $accessToken;
        $this->getDataByHGet(static::DINGTALK_TOKEN_PREFIX, $this->getAgentId(), function() use ($accessToken) {
            return $accessToken;
        }, 5, $expiresIn - 60, true);// 重新刷新缓存

        return $this;
    }

    /**
     * 获取签名
     * User: Sweeper
     * Time: 2023/1/10 15:52
     * @param string|null $secret
     * @return array
     */
    protected function getSign(string $secret): array
    {
        $timestamp = get_microtime();
        $sign      = base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true));
        $sign      = str_to_utf8(urlencode($sign));

        return ['timestamp' => $timestamp, 'sign' => $sign];
    }

    /**
     * 生成签名后的地址
     * User: Sweeper
     * Time: 2023/1/10 17:02
     * @param string $path
     * @return string
     */
    protected function generateSignUrl(string $path): string
    {
        $secret      = $this->getRobotSecret();
        $accessToken = $this->getRobotAccessToken();
        if (!$secret || !$accessToken) {
            throw new \InvalidArgumentException('参数无效，请检查 Secret 及 AccessToken');
        }
        ['timestamp' => $timestamp, 'sign' => $sign] = $this->getSign($secret);

        return $this->buildRequestUri("{$path}?access_token={$accessToken}&timestamp={$timestamp}&sign={$sign}");
    }

    /**
     * 解析平台响应内容
     * User: Sweeper
     * Time: 2023/1/10 11:51
     * @param ResponseInterface $response
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    public function resolveResponse(ResponseInterface $response): Response
    {
        //返回结果解析
        $httpCode        = $response->getStatusCode();
        $responseContent = json_decode($response->getBody()->getContents() ?? '', true);
        $errCode         = $responseContent['errcode'] ?? 0;
        $message         = $responseContent['message'] ?? $response->getReasonPhrase();
        $responseCode    = HttpCode::OK;
        if ($httpCode !== HttpCode::OK || $errCode !== 0) {// HttpCode 为 200 ， code 0 为成功
            $message      = "接口请求口成功，返回错误：{$message}[" . HttpCode::INTERNAL_SERVER_ERROR . ']';
            $responseCode = HttpCode::INTERNAL_SERVER_ERROR;
        }
        $responseContent['data'] = $responseContent['data'] ?? [];

        return new Response($responseCode, $message, $responseContent, $responseContent['uuid'] ?? '');
    }

    /**
     * 生成 text 格式消息数据
     * User: Sweeper
     * Time: 2023/1/11 19:37
     * @param string $content
     * @param bool   $isAtAll
     * @param array  $atMobiles
     * @param array  $atUserIds
     * @return array
     */
    public static function generateTextData(string $content, bool $isAtAll = false, array $atMobiles = [], array $atUserIds = []): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_TEXT];
        foreach ($atMobiles as $atMobile) {
            $content .= "@{$atMobile}";
        }
        foreach ($atUserIds as $atUserId) {
            $content .= "@{$atUserId}";
        }

        return array_replace_recursive($format, [
            'text' => [
                'content' => $content,// 消息内容。 String 必填
            ],
            'at'   => [
                'atMobiles' => $atMobiles,// 被@人的手机号。 Array 注意 在content里添加@人的手机号，且只有在群内的成员才可被@，非群内成员手机号会被脱敏。
                'atUserIds' => $atUserIds,// 被@人的用户userid。Array 注意 在content里添加@人的userid。
                'isAtAll'   => $isAtAll,// 是否@所有人。Boolean
            ],
        ]);
    }

    /**
     * 生成 link 格式消息数据
     * User: Sweeper
     * Time: 2023/1/11 19:44
     * @param string $title
     * @param string $text
     * @param string $messageUrl
     * @param string $picUrl
     * @return array
     */
    public static function generateLinkData(string $title, string $text, string $messageUrl, string $picUrl = ''): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_LINK];

        return array_replace_recursive($format, [
            'link' => [
                'title'      => $title,// 消息标题。String 必填
                'text'       => $text,// 消息内容。如果太长只会部分展示。 String 必填
                'messageUrl' => $messageUrl,// 点击消息跳转的URL， String 必填
                'picUrl'     => $picUrl,// 图片URL。String
            ],
        ]);
    }

    /**
     * 生成 markdown 格式消息数据
     * User: Sweeper
     * Time: 2023/1/11 19:38
     * @param string $title
     * @param string $text
     * @param bool   $isAtAll
     * @param array  $atMobiles
     * @param array  $atUserIds
     * @return array
     */
    public static function generateMarkdownData(string $title, string $text, bool $isAtAll = false, array $atMobiles = [], array $atUserIds = []): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_MARKDOWN];
        foreach ($atMobiles as $atMobile) {
            $text .= "@{$atMobile}";
        }
        foreach ($atUserIds as $atUserId) {
            $text .= "@{$atUserId}";
        }

        return array_replace_recursive($format, [
            'markdown' => [
                'title' => $title,// 首屏会话透出的展示内容。 String 必填
                'text'  => $text,// markdown格式的消息。 String 必填
            ],
            'at'       => [
                'atMobiles' => $atMobiles,// 被@人的手机号。 Array 注意 在content里添加@人的手机号，且只有在群内的成员才可被@，非群内成员手机号会被脱敏。
                'atUserIds' => $atUserIds,// 被@人的用户userid。Array 注意 在content里添加@人的userid。
                'isAtAll'   => $isAtAll,// 是否@所有人。Boolean
            ],
        ]);
    }

    /**
     * 生成 整体跳转ActionCard类型 消息数据
     * User: Sweeper
     * Time: 2023/1/11 20:08
     * @param string $title
     * @param string $text
     * @param string $singleTitle
     * @param string $singleURL
     * @param string $btnOrientation
     * @return array
     */
    public static function generateActionCardWholeData(string $title, string $text, string $singleTitle, string $singleURL, string $btnOrientation = ''): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_ACTION_CARD][static::ACTION_CARD_WHOLE];

        return array_replace_recursive($format, [
            'actionCard' => [
                'title'          => $title,// 首屏会话透出的展示内容。String 必填
                'text'           => $text,// markdown格式的消息。 String 必填
                'singleTitle'    => $singleTitle,// 单个按钮的标题。String 必填 注意 设置此项和singleURL后，btns无效。
                'singleURL'      => $singleURL,// 点击消息跳转的URL。String 必填
                'btnOrientation' => $btnOrientation,// 0：按钮竖直排列、1：按钮横向排列
            ],
        ]);
    }

    /**
     * 生成独立跳转ActionCard类型消息数据
     * User: Sweeper
     * Time: 2023/1/11 20:10
     * @param string $title
     * @param string $text
     * @param string $btnTitle
     * @param string $actionURL
     * @param string $btnOrientation
     * @param array  $btns 如果有多个的话，写到这个变量会自动追加到 btns 上，总个数为 count($btns) + 1
     * @return array
     */
    public static function generateActionCardIndependentData(string $title, string $text, string $btnTitle, string $actionURL, string $btnOrientation = '', array $btns = []): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_ACTION_CARD][static::ACTION_CARD_INDEPENDENT];

        return array_replace_recursive($format, [
            'actionCard' => [
                'title'          => $title,// 首屏会话透出的展示内容。String 必填
                'text'           => $text,// markdown格式的消息。 String 必填
                'btns'           => array_merge([
                    [
                        'title'     => $btnTitle,// 按钮标题。String 必填
                        'actionURL' => $actionURL,// 点击按钮触发的URL。String 必填
                    ],
                ], $btns),
                'btnOrientation' => $btnOrientation,// 0：按钮竖直排列、1：按钮横向排列
            ],
        ]);
    }

    /**
     * 生成 feedCard 格式消息数据
     * User: Sweeper
     * Time: 2023/1/11 19:38
     * @param string $title
     * @param string $messageURL
     * @param string $picURL
     * @param array  $links 如果有多个的话，写到这个变量会自动追加到 links 上，总个数为 count($links) + 1
     * @return array
     */
    public static function generateFeedCardData(string $title, string $messageURL, string $picURL = '', array $links = []): array
    {
        $format = static::MSG_TYPE_FORMAT[static::MSG_TYPE_FEED_CARD];

        return array_replace_recursive($format, [
            'feedCard' => [
                'links' => array_merge([
                    [
                        'title'      => $title,// 单条信息文本。String 必填
                        'messageURL' => $messageURL,// 点击单条信息到跳转链接。String 必填
                        'picURL'     => $picURL,// 单条信息后面图片的URL。
                    ],
                ], $links),
            ],
        ]);
    }

}
