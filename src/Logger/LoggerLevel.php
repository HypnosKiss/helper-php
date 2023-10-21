<?php

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2022/12/16 10:34
 */

namespace Sweeper\HelperPhp\Logger;

use Psr\Log\LogLevel;

/**
 * defined referred to PSR-3
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/21 23:11
 * @Package \Sweeper\HelperPhp\Logger\LoggerLevel
 */
class LoggerLevel extends LogLevel
{

    /** @var string[] log level error level order by severity */
    public const SEVERITY_ORDER = [
        self::DEBUG     => 0,
        self::INFO      => 1,
        self::NOTICE    => 2,
        self::WARNING   => 3,
        self::ERROR     => 4,
        self::CRITICAL  => 5,
        self::ALERT     => 6,
        self::EMERGENCY => 7,
    ];

    /** @var string[] PHP error code mapping to Logger level */
    public const PHP_ERROR_MAPS = [
        E_ERROR             => self::CRITICAL,
        E_WARNING           => self::WARNING,
        E_PARSE             => self::ALERT,
        E_NOTICE            => self::NOTICE,
        E_CORE_ERROR        => self::CRITICAL,
        E_CORE_WARNING      => self::WARNING,
        E_COMPILE_ERROR     => self::ALERT,
        E_COMPILE_WARNING   => self::WARNING,
        E_USER_ERROR        => self::ERROR,
        E_USER_WARNING      => self::WARNING,
        E_USER_NOTICE       => self::NOTICE,
        E_STRICT            => self::NOTICE,
        E_RECOVERABLE_ERROR => self::ERROR,
        E_DEPRECATED        => self::NOTICE,
        E_USER_DEPRECATED   => self::NOTICE,
    ];

    /**
     * log level compare
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/21 21:29
     * @param $lv1
     * @param $lv2
     * @return int 0 if lv1 equal to lv2, 1 if lv1 more serious than lv2, -1 if lv1 less serious than lv2
     */
    public static function levelCompare($lv1, $lv2): int
    {
        return static::SEVERITY_ORDER[$lv1] <=> static::SEVERITY_ORDER[$lv2];// 如果 $lv1 > $lv2，则返回的值为 1；如果 $lv1 == $lv2，则返回的值为 0；如果 $lv1 < $lv2，则返回的值为 -1；
    }

}
