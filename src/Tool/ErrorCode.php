<?php

namespace Sweeper\HelperPhp\Tool;

/**
 * 接口错误码统一管理
 * 先定义异常大的分类，同一类但更加细分的异常，
 * 在大类code后，加两位，并从01累加，这样可以保证，每个分类下可以定义最多99种细分
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:42
 * @Path \Sweeper\HelperPhp\Tool\ErrorCode
 */
class ErrorCode
{

    /** @var int 通用成功 */
    public const SUCCESS = 0;

    /** @var int 通用错误 */
    public const FAILURE = 1;

    /**
     * 异常--------------------------------------------------------------
     */
    public const EXCEPTION = -100;

    /**
     * 通用错误--------------------------------------------------------------
     */

    public const UNKNOWN_REQUEST     = 100; // 通用的未知请求

    public const ERROR_REQUEST       = 101; // 通用的错误请求

    public const PARAMS_ERROR        = 102; // 通用的参数错误

    public const PARAMS_EXISTS       = 103; //通用的参数不存在

    public const PARAMS_TYPE         = 104; //通用的参数类型错误

    public const METHOD_NOT_EXIST    = 105; //通用的方法不存在

    public const DATA_NOT_EXIST      = 106; //通用数据不存在

    public const UN_OPERATE_TOO_LONG = 107; //页面长久未操作，导致CSRF过期

    public const LOGIC_NOT_MATCH     = 108; //逻辑不匹配

    /**
     * 数据库错误--------------------------------------------------------------
     */
    public const DB_INSERT          = 201; // 数据库写入异常

    public const DB_QUERY           = 202; // 数据库查询异常

    public const DB_QUERY_RESULT    = 20201; // 数据库查询结果不符合业务导致的程序中止

    public const DB_QUERY_EXIST     = 20202; // 数据库查询结果已存在

    public const DB_QUERY_NOT_EXIST = 20203; // 数据库查询结果不存在

    public const DB_UPDATE          = 203; // 数据库更新异常

    public const DB_DELETE          = 204; // 数据库删除异常

    /**
     * 代码实现错误--------------------------------------------------------------
     */

    public const CODE_CLASS_NOT_EXIST = 301; // 类不存在

    /**
     * 第三方服务错误--------------------------------------------------------------
     */

    public const SERVICE_RESPONSE              = 401;   // 服务响应异常

    public const SERVICE_RESPONSE_ERROR_SELLER = 40101; // 卖家服务响应异常

    public const SERVICE_RESPONSE_ERROR_TOEKN  = 40102; // 卖家服务TOKEN异常

    public const SERVICE_RESPONSE_ERROR        = 40103; // 服务响应错误

    /**
     * 平台帐号授权--------------------------------------------------------------
     */

    public const PLATFORM_AUTH        = 501;   // 平台授权异常

    public const PLATFORM_AUTH_EXPIRE = 50101; // 平台授权过期

    public const PLATFORM_AUTH_TOKEN  = 50102; // 平台授权TOKEN问题

}