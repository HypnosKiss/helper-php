<?php

namespace Sweeper\HelperPhp\DingTalk;

use dingtalk\DingTalkClient;
use dingtalk\DingTalkConstant;
use dingtalk\request\OapiBlackboardListtoptenRequest;
use dingtalk\request\OapiCallBackGetCallBackFailedResultRequest;
use dingtalk\request\OapiCallBackGetCallBackRequest;
use dingtalk\request\OapiChatCreateRequest;
use dingtalk\request\OapiChatGetReadListRequest;
use dingtalk\request\OapiChatGetRequest;
use dingtalk\request\OapiChatSendRequest;
use dingtalk\request\OapiChatSubadminUpdateRequest;
use dingtalk\request\OapiChatUpdateRequest;
use dingtalk\request\OapiMessageCorpconversationAsyncsendV2Request;
use dingtalk\request\OapiMessageCorpconversationGetsendresultRequest;
use dingtalk\request\OapiMessageCorpconversationRecallRequest;
use dingtalk\request\OapiMessageCorpconversationStatusBarUpdateRequest;
use dingtalk\request\OapiRoleGetrolegroupRequest;
use dingtalk\request\OapiRoleListRequest;
use dingtalk\request\OapiSmartworkHrmEmployeeListRequest;
use dingtalk\request\OapiSnsGetuserinfoBycodeRequest;
use dingtalk\request\OapiUserGetbyunionidRequest;
use dingtalk\request\OapiV2DepartmentGetRequest;
use dingtalk\request\OapiV2DepartmentListsubRequest;
use dingtalk\request\OapiV2UserGetRequest;
use dingtalk\request\OapiV2UserGetuserinfoRequest;
use dingtalk\request\OapiV2UserListRequest;
use dingtalk\request\OapiProcessinstanceListidsRequest;
use dingtalk\request\OapiProcessinstanceGetRequest;
use Sweeper\DesignPattern\Traits\MultiPattern;
use Sweeper\HelperPhp\Tool\ExportExcelChunk;
use Sweeper\HelperPhp\Traits\RedisCache;

/**
 * 钉钉SNS功能
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/18 15:36
 * @Package \Sweeper\HelperPhp\DingTalk\DingTalkSns
 */
class DingTalkSns extends Request
{

    use MultiPattern, RedisCache;

    public const STATE_LOGIN = 'login';

    public const STATE_BIND = 'bind';

    public const STATE_FREE = 'free';

    /**
     * 创建扫码登录url
     * User: Sweeper
     * Time: 2023/9/5 19:27
     * @param string $state
     * @return string
     */
    public function createLoginUrl(string $state = self::STATE_LOGIN): string
    {
        $appConfig = $this->getConfig();

        return sprintf('https://oapi.dingtalk.com/connect/qrconnect?appid=%s&response_type=code&scope=snsapi_login&state=%s&redirect_uri=%s', $appConfig['sns_login']['appId'] ?? '', $state, urlencode($appConfig['sns_login']['callback']));
    }

    /**
     * 创建扫码登录url
     * User: Sweeper
     * Time: 2023/9/5 19:28
     * @param string $state
     * @return string
     */
    public function createUrlByLoginTmpCode(string $state = self::STATE_LOGIN): string
    {
        $appConfig = $this->getConfig();

        return sprintf('https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=%s&response_type=code&scope=snsapi_login&state=%s&redirect_uri=%s&loginTmpCode=', $appConfig['sns_login']['appId'] ?? '', $state, urlencode($appConfig['sns_login']['callback']));
    }

    /**
     * 创建钉钉内免登授权url
     * User: Sweeper
     * Time: 2023/9/5 19:29
     * @param $callbackUrl
     * @return string
     */
    public function createUrlFreeLogin($callbackUrl): string
    {
        $appConfig = $this->getConfig();
        $callback  = "{$appConfig['sns_login']['callback']}&site_url=" . urlencode($callbackUrl);

        return sprintf('https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=%s&response_type=code&scope=snsapi_auth&state=%s&redirect_uri=%s', $appConfig['sns_login']['appId'] ?? '', static::STATE_FREE, urlencode($callback));
    }

    /**
     * 获取 Union id
     * User: Sweeper
     * Time: 2023/9/5 19:30
     * @param $code
     * @return \SimpleXMLElement|string
     */
    public function getUnionIdInfoByCode($code)
    {
        $appConfig = $this->getConfig();
        $client    = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $request   = new OapiSnsGetuserinfoBycodeRequest();
        $request->setTmpAuthCode($code);
        $response = $client->executeWithAccessKey($request, 'https://oapi.dingtalk.com/sns/getuserinfo_bycode', $appConfig['sns_login']['appId'] ?? '', $appConfig['sns_login']['appSecret'] ?? '');

        return $response->user_info->unionid ?? '';
    }

    /**
     * 获取接口 access_token
     * @param bool $refresh
     * @param bool $returnRawData
     * @return mixed
     */
    public function getDingTalkAccessToken(bool $refresh = false, bool $returnRawData = false)
    {
        $appConfig = $this->getConfig();

        return current($this->getDataByHGet(__FUNCTION__, '__DING_TALK_ACCESS_TOKEN__' . $appConfig['app']['AgentId'] . $returnRawData, function() use ($appConfig, $returnRawData) {
            $client              = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
            $tokenUrl            = sprintf('https://oapi.dingtalk.com/gettoken?appkey=%s&appsecret=%s', $appConfig['app']['AppKey'], $appConfig['app']['AppSecret']);
            $responseAccessToken = $client->curl_get($tokenUrl, []);
            $responseAccessToken = json_decode($responseAccessToken, true);
            if ($returnRawData) {
                return $responseAccessToken;
            }

            return $responseAccessToken->access_token;
        }, 5, 7200, $refresh));
    }

    /**
     * 获取 User id
     * @param $unionId
     * @param $accessToken
     * @return array
     */
    public function getUserIdByUnionId($unionId, $accessToken): array
    {
        $client      = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestUser = new OapiUserGetbyunionidRequest();
        $requestUser->setUnionid($unionId);
        $responseUser = $client->execute($requestUser, $accessToken, 'https://oapi.dingtalk.com/topapi/user/getbyunionid');
        if ($responseUser->errcode == 0 && $responseUser->errmsg == 'ok') {
            return [true, $responseUser->errmsg, $responseUser->result->userid];
        }

        return [false, $responseUser->errmsg, $responseUser->result->userid ?? 0];
    }

    /**
     * 获取用户信息
     * @param $userId
     * @return array
     */
    public function getUserInfo($userId): array
    {
        $client      = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestUser = new OapiV2UserGetRequest();
        $requestUser->setUserid($userId);
        $requestUser->setLanguage('zh_CN');
        $responseUser = $client->execute($requestUser, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/v2/user/get');
        if ($responseUser->errcode == 0 && $responseUser->errmsg == 'ok') {
            return [true, $responseUser->errmsg, $responseUser->result];
        }

        return [false, $responseUser->errmsg, $responseUser->result ?? []];
    }

    /**
     * 通过免登码获取用户信息
     * @param $code
     * @return array
     */
    public function getUserInfoByCode($code): array
    {
        $client      = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestUser = new OapiV2UserGetuserinfoRequest();
        $requestUser->setCode($code);
        $responseUser = $client->execute($requestUser, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/v2/user/getuserinfo');
        if ($responseUser->errcode == 0 && $responseUser->errmsg == 'ok') {
            return [true, $responseUser->errmsg, (array)$responseUser->result];
        }

        return [false, $responseUser->errmsg, $responseUser->result ?? []];
    }

    /**
     * 发送工作通知
     * @param array $message
     * @param array $userList
     * @param array $deptIdList
     * @param bool  $toAllUser
     * @return false|\SimpleXMLElement
     */
    public function sendWorkMessage(array $message, array $userList = [], array $deptIdList = [], bool $toAllUser = false)
    {
        $client         = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestMessage = new OapiMessageCorpconversationAsyncsendV2Request();
        $appConfig      = $this->getConfig();
        $requestMessage->setAgentId($appConfig['app']['AgentId']);
        if (!$toAllUser) {
            if (!$userList && !$deptIdList) {
                return false;
            }
            if ($userList) {
                $requestMessage->setUseridList(sprintf('%s', implode(',', $userList)));
            }
            if ($deptIdList) {
                $requestMessage->setDeptIdList(sprintf('%s', implode(',', $deptIdList)));
            }
        }
        $requestMessage->setToAllUser($toAllUser);
        $requestMessage->setMsg($message);
        $responseMessage = $client->execute($requestMessage, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return $responseMessage->task_id;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 更新OA工作通知消息的状态
     * @param        $taskId
     * @param        $statusValue
     * @param string $statusColor
     * @return \SimpleXMLElement
     */
    public function updateOaWorkMessageStatus($taskId, $statusValue, $statusColor = '0xFF78C06E'): \SimpleXMLElement
    {
        $client         = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestMessage = new OapiMessageCorpconversationStatusBarUpdateRequest();
        $app_config     = $this->getConfig();
        $requestMessage->setAgentId($app_config['app']['AgentId']);
        $requestMessage->setStatusValue($statusValue);
        $requestMessage->setTaskId($taskId);
        $requestMessage->setStatusBg($statusColor);
        $responseMessage = $client->execute($requestMessage, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/message/corpconversation/status_bar/update');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return $responseMessage;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 撤回工作消息通知
     * @param $msg_task_id
     * @return \dingtalk\ResultSet|false|mixed|\SimpleXMLElement
     */
    public function recallWorkMessage($msg_task_id)
    {
        $client         = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestMessage = new OapiMessageCorpconversationRecallRequest();
        $app_config     = $this->getConfig();
        $requestMessage->setAgentId($app_config['app']['AgentId']);
        $requestMessage->setMsgTaskId($msg_task_id);
        $responseMessage = $client->execute($requestMessage, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/message/corpconversation/recall');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return $responseMessage;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 获取发送结果/已读/未读
     * @param $task_id
     * @return array
     */
    public function getSendResult($task_id): array
    {
        $client         = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $requestMessage = new OapiMessageCorpconversationGetsendresultRequest();
        $app_config     = $this->getConfig();
        $requestMessage->setAgentId($app_config['app']['AgentId']);
        $requestMessage->setTaskId($task_id);
        $responseMessage = $client->execute($requestMessage, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/message/corpconversation/getsendresult');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return (array)$responseMessage->send_result;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 创建群
     * @param       $name
     * @param       $owner
     * @param       $userIdList
     * @param array $config
     * @return array
     */
    public function createChat($name, $owner, $userIdList, array $config = []): array
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatCreateRequest();
        $chat->setName($name);
        $chat->setOwner($owner);
        $chat->setUseridlist($userIdList);
        $chat->setShowHistoryType($config['showHistoryType'] ?? 0);
        $chat->setSearchable($config['searchable'] ?? 0);
        $chat->setValidationType($config['validationType'] ?? 0);
        $chat->setMentionAllAuthority($config['mentionAllAuthority'] ?? 0);
        $chat->setManagementType($config['managementType'] ?? 0);
        $chat->setChatBannedType($config['chatBannedType'] ?? 0);
        $responseChat = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/chat/create');
        if ($responseChat->errcode == 0 && $responseChat->errmsg == 'ok') {
            return (array)$responseChat;
        }
        throw  new \RuntimeException($responseChat->errmsg);
    }

    /**
     * 获取群会话信息
     * @param $chat_id
     * @return mixed
     */
    public function getChat($chat_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatGetRequest();
        $chat->setChatid($chat_id);
        $responseChat = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/chat/get');
        if ($responseChat->errcode == 0 && $responseChat->errmsg == 'ok') {
            return json_decode(json_encode($responseChat->chat_info), true);
        }
        throw  new \RuntimeException($responseChat->errmsg);
    }

    /**
     * 修改群会话
     * @param       $chat_id
     * @param array $info
     * @return bool
     */
    public function updateChat($chat_id, array $info = []): bool
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatUpdateRequest();
        $chat->setChatid($chat_id);
        foreach ($info as $key => $value) {
            $func = 'set' . ucfirst($key);
            $chat->{$func}($value);
        }
        $responseChat = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/chat/update');
        if ($responseChat->errcode == 0 && $responseChat->errmsg == 'ok') {
            return true;
        }
        throw  new \RuntimeException($responseChat->errmsg);
    }

    /**
     * 设置、删除管理员
     * @param $chat_id
     * @param $userIds
     * @param $role
     * @return bool
     */
    public function setChatAdmin($chat_id, $userIds, $role): bool
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatSubadminUpdateRequest();
        $chat->setChatid($chat_id);
        $chat->setUserids($userIds);
        $chat->setRole($role);
        $responseChat = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/chat/subadmin/update');
        if ($responseChat->errcode == 0 && $responseChat->errmsg == 'ok') {
            return true;
        }
        throw  new \RuntimeException($responseChat->errmsg);
    }

    /**
     * 发送消息到企业群
     * @param $chat_id
     * @param $msg
     * @return \SimpleXMLElement
     */
    public function sendChatMessage($msg, $chat_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatSendRequest();
        $chat->setChatid($chat_id);
        $chat->setMsg($msg);
        $responseMessage = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/chat/send');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return $responseMessage->messageId;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 查询已读人数
     * @param     $message_id
     * @param int $size
     * @param int $cursor
     * @return array
     */
    public function getChatReadList($message_id, $size = 100, $cursor = 0)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
        $chat   = new OapiChatGetReadListRequest();
        $chat->setMessageId($message_id);
        $chat->setCursor($cursor);
        $chat->setSize($size);
        $responseMessage = $client->execute($chat, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/chat/getReadList');
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return (array)$responseMessage;
        }

        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 获取下级部门列表
     * @param int $dept_id 部门ID
     * @return \dingtalk\ResultSet|mixed|\SimpleXMLElement|null
     */
    public function getDepartmentListSub($dept_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $dept   = new OapiV2DepartmentListsubRequest();
        $dept->setDeptId($dept_id);
        $dept->setLanguage('zh_CN');
        $responseDept = $client->execute($dept, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/v2/department/listsub');
        if ($responseDept->errcode == 0 && $responseDept->errmsg == 'ok') {
            return (array)$responseDept;
        }
        throw new \RuntimeException($responseDept->errmsg);
    }

    /**
     * 获取部门用户详情
     * @param int  $dept_id              部门ID
     * @param int  $cursor               页查询的游标，最开始传0，后续传返回参数中的next_cursor值
     * @param int  $size                 分页大小
     * @param bool $contain_access_limit 是否返回访问受限的员工
     * @return array|bool
     */
    public function getDepartmentUserInfo($dept_id, $cursor = 0, $size = 20, $contain_access_limit = true)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $dept   = new OapiV2UserListRequest();
        $dept->setDeptId($dept_id);
        $dept->setCursor($cursor);
        $dept->setSize($size);
        $dept->setContainAccessLimit($contain_access_limit);
        $dept->setLanguage('zh_CN');
        $responseDept = $client->execute($dept, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/v2/user/list');
        if ($responseDept->errcode == 0 && $responseDept->errmsg == 'ok') {
            return json_decode(json_encode($responseDept), true);
        }
        throw new \RuntimeException($responseDept->errmsg);
    }

    /**
     * 获取部门详情
     * @param $dept_id
     * @return bool|mixed
     */
    public function getDepartmentDetail($dept_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $dept   = new OapiV2DepartmentGetRequest();
        $dept->setDeptId($dept_id);
        $dept->setLanguage('zh_CN');
        $responseDept = $client->execute($dept, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/v2/department/get');
        if ($responseDept->errcode == 0 && $responseDept->errmsg == 'ok') {
            return json_decode(json_encode($responseDept), true);
        }
        throw new \RuntimeException($responseDept->errmsg);
    }

    /**
     * @param $group_id
     * @return array|bool
     */
    public function getRoleGroup($group_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $group  = new OapiRoleGetrolegroupRequest();
        $group->setGroupId($group_id);
        $responseGroup = $client->execute($group, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/role/getrolegroup');
        if ($responseGroup->errcode == 0 && $responseGroup->errmsg == 'ok') {
            return json_decode(json_encode($responseGroup), true);
        }
        throw new \RuntimeException($responseGroup->errmsg);
    }

    /**
     * @param int $size
     * @param int $offset
     * @return array|bool
     */
    public function getRoleList($size = 20, $offset = 0)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $role   = new OapiRoleListRequest();
        $role->setSize($size);
        $role->setOffset($offset);
        $responseRole = $client->execute($role, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/role/list');
        if ($responseRole->errcode == 0 && $responseRole->errmsg == 'ok') {
            return json_decode(json_encode($responseRole), true);
        }
        throw new \RuntimeException($responseRole->errmsg);
    }

    /**
     * 花名册信息
     * @param        $userid_list
     * @param string $field_filter_list
     * @return array|bool
     */
    public function hrmEmployeeList($userid_list, $field_filter_list)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $hrm    = new OapiSmartworkHrmEmployeeListRequest();
        $hrm->setUseridList(is_array($userid_list) ? implode(',', $userid_list) : $userid_list);
        if ($field_filter_list) {
            $hrm->setFieldFilterList(is_array($field_filter_list) ? implode(',', $field_filter_list) : $field_filter_list);
        }
        $app_config = $this->getConfig();
        $hrm->setAgentid($app_config['app']['AgentId']);
        $responseRole = $client->execute($hrm, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/smartwork/hrm/employee/v2/list');
        if ($responseRole->errcode == 0 && $responseRole->errmsg == 'ok') {
            return json_decode(json_encode($responseRole), true);
        }
        throw new \RuntimeException($responseRole->errmsg);
    }

    /**
     * 获取推送失败的事件列表
     * @return bool|mixed
     */
    public function getCallbackFailedResult()
    {
        $client           = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
        $url              = sprintf('https://oapi.dingtalk.com/call_back/get_call_back_failed_result?access_token=%s', $this->getDingTalkAccessToken());
        $responseCallback = $client->curl_get($url, []);
        if ($responseCallback->errcode == 0 && $responseCallback->errmsg == 'ok') {
            return json_decode(json_encode($responseCallback), true);
        }
        throw new \RuntimeException($responseCallback->errmsg);
    }

    /**
     * 获取公告
     * @param $userid
     * @return bool|mixed
     */
    public function getBlackboard($userid)
    {
        $client     = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $blackboard = new OapiBlackboardListtoptenRequest();
        $blackboard->setUserid($userid);
        $responseBoard = $client->execute($blackboard, $this->getDingTalkAccessToken(), 'https://oapi.dingtalk.com/topapi/blackboard/listtopten');
        if ($responseBoard->errcode == 0 && $responseBoard->errmsg == 'ok') {
            return json_decode(json_encode($responseBoard), true);
        }

        throw new \RuntimeException($responseBoard->errmsg);
    }

    /**
     * 链接转换
     * @param      $url
     * @param bool $pc_slide false:浏览器打开， true:钉钉打开
     * @return string
     */
    public function openUrl($url, $pc_slide = false): string
    {
        return sprintf('dingtalk://dingtalkclient/page/link?url=%s&pc_slide=%s', urlencode($url), $pc_slide ? 'true' : 'false');
    }

    /**
     * 发送机器人消息
     * @param $message
     * @param $robot_url
     * @param $secret
     * @return mixed
     */
    public function sendRobotMessage($message, $robot_url, $secret)
    {
        $sign_info       = $this->getRobotSign($secret);
        $url             = $robot_url . "&timestamp=" . $sign_info['timestamp'] . "&sign=" . $sign_info['sign'];
        $data            = is_array($message) ? json_encode($message, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : $message;
        $responseMessage = $this->robotPost($url, $data);
        $responseMessage = json_decode($responseMessage);
        if ($responseMessage->errcode == 0 && $responseMessage->errmsg == 'ok') {
            return true;
        }
        throw new \RuntimeException($responseMessage->errmsg);
    }

    /**
     * 获取签名
     * @param $secret
     * @return array
     */
    private function getRobotSign($secret): array
    {
        $timestamp = $this->getMillisecond();
        $sign      = base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true));
        $sign      = $this->strToUtf8(urlencode($sign));

        return ['timestamp' => $timestamp, 'sign' => $sign];
    }

    /**
     * 获取当前时间戳毫秒级
     * @return float
     */
    private function getMillisecond(): float
    {
        [$millisecond, $second] = explode(' ', microtime());

        return (float)sprintf('%.0f', ((float)$millisecond + (float)$second) * 1000);
    }

    /**
     * 字符串转 UTF-8
     * @param $str
     * @return string|string[]|null
     */
    private function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
        if ($encode === 'UTF-8') {
            return $str;
        }

        return mb_convert_encoding($str, 'UTF-8', $encode);
    }

    /**
     * POST请求
     * @param       $url
     * @param       $data
     * @param array $header
     * @return mixed
     */
    private function robotPost($url, $data, array $header = ['Content-Type: application/json;charset=utf-8'])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * 根据数据转成excel文件上传
     * @param $data_list
     * @param $header
     * @param $name
     * @return bool|string
     */
    public function uploadMediaFile($data_list, $header, $name)
    {
        $year       = date('Y');
        $targetPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR;
        if (!is_dir($targetPath)) {
            if (!mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $targetPath));
            }
            chmod($targetPath, 0777);
        }
        $file_name = $targetPath . '/' . $name . '.xls';
        if (file_exists($file_name)) {
            @unlink($file_name);
        }
        $excel_file_fp = fopen($file_name, 'a');
        if ($header) {
            $xml_str = ExportExcelChunk::getXmlHeadString($header);
            fwrite($excel_file_fp, $xml_str);
        }
        foreach ($data_list as $row) {
            $data_xml_str = ExportExcelChunk::getXmlBodyString($row);
            fwrite($excel_file_fp, $data_xml_str);
        }
        fclose($excel_file_fp);//关闭文件
        //上传钉钉获取media_id
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => sprintf('https://oapi.dingtalk.com/media/upload?access_token=%s', $this->getDingTalkAccessToken()),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => ['media' => new \CURLFILE($file_name), 'type' => 'file'],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        @unlink($file_name);
        if (!$response) {
            return false;
        }
        $info = json_decode($response, true);
        if ($info['errcode'] == 0 && $info['errmsg'] == 'ok') {
            return $info['media_id'];
        }

        return false;
    }

    /**
     * 获取线上tokenAccess
     */
    public function getOnlineTokenAccess()
    {
        $timestamp        = time();
        $DING_TALK_SECRET = 'xxx';
        $KEY              = 'xxx';
        $type             = 'ding_talk';
        $md5_sign         = md5(sprintf('%s%s%s%s', $DING_TALK_SECRET, $timestamp, $type, $DING_TALK_SECRET));
        $sign             = md5($KEY . $md5_sign . $KEY);
        $url              = sprintf('https://sales.huapx.com/erp.php?r=api/ken/dt_token&type=%s&timestamp=%s&sign=%s', $type, $timestamp, $sign);
        $data             = file_get_contents($url);
        $array            = json_decode($data, true);

        return $array['data']['access_token'];
    }

    /**
     * 获取审批实例ID列表
     * @param     $accessToken
     * @param     $process_code
     * @param     $start_time
     * @param     $end_time
     * @param int $size
     * @param int $cursor
     * @return array|bool
     */
    public function getProcessInstanceListIds($accessToken, $process_code, $start_time, $end_time, $size = 20, $cursor = 0)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $hrm    = new OapiProcessinstanceListidsRequest();
        $hrm->setProcessCode($process_code);
        $hrm->setStartTime($start_time);
        if ($end_time) {
            $hrm->setEndTime($end_time);
        }
        $hrm->setSize($size);
        $hrm->setCursor($cursor);
        $responseRole = $client->execute($hrm, $accessToken, "https://oapi.dingtalk.com/topapi/processinstance/listids");
        if ($responseRole->errcode == 0 && $responseRole->errmsg == 'ok') {
            return (array)$responseRole->result;
        }

        if ($responseRole->errcode == 88 && $responseRole->errmsg == '不合法的access_token') {
            $accessToken = $this->getDingTalkAccessToken(true, true);
            self::getProcessInstanceListIds($accessToken, $process_code, $start_time, $end_time, $size, $cursor);
        } else {
            throw new \RuntimeException($responseRole->errmsg);
        }
    }

    /**
     * 获取审批实例详情
     * @param $accessToken
     * @param $process_instance_id
     * @return array|bool
     */
    public function getProcessInstance($accessToken, $process_instance_id)
    {
        $client = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST, DingTalkConstant::$FORMAT_JSON);
        $req    = new OapiProcessinstanceGetRequest;
        $req->setProcessInstanceId($process_instance_id);
        $resp = $client->execute($req, $accessToken, "https://oapi.dingtalk.com/topapi/processinstance/get");
        if ($resp->errcode == 0 && $resp->errmsg == 'ok') {
            return (array)$resp->process_instance;
        }

        if ($resp->errcode == 88 && $resp->errmsg == '不合法的access_token') {
            $accessToken = $this->getDingTalkAccessToken(true, true);
            self::getProcessInstance($accessToken, $process_instance_id);
        } else {
            throw new \RuntimeException($resp->errmsg);
        }
    }

}