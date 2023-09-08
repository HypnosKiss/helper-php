<?php

namespace Sweeper\HelperPhp\Tool;

/**
 * 雪花算法
 * Class Snowflake
 * @see     Snowflake::uniqueId()
 * @package ande\toolkit\snowflake
 */
class Snowflake
{

    /** @var int 开始时间,固定一个小于当前时间的毫秒数\ */
    public const        EPOCH    = 1639034366020;

    public const        MAX12BIT = 4095;

    public const        MAX41BIT = 1099511627775;

    /** @var int 机器id */
    public static $machineId = null;

    public static function getMachineId(): ?int
    {
        return static::$machineId;
    }

    /**
     * @param int|null $machineId
     */
    public static function setMachineId(?int $machineId = 1000000001): void
    {
        static::$machineId = $machineId;
    }

    /**
     * 唯一ID
     * User: Sweeper
     * Time: 2023/9/6 9:10
     * @param string $prefix
     * @return string
     */
    public static function uniqueId(string $prefix = ''): string
    {
        $time = floor(microtime(true) * 1000);
        $time -= static::EPOCH;
        $base = decbin(static::MAX41BIT + $time);
        if (!static::$machineId) {
            $machineId = static::$machineId;
        } else {
            $machineId = str_pad(decbin(static::$machineId), 10, "0", STR_PAD_LEFT);
        }
        $random = str_pad(decbin(random_int(0, static::MAX12BIT)), 12, "0", STR_PAD_LEFT);
        $base   .= $machineId . $random;

        return $prefix . bindec($base);
    }

}