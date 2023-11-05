<?php

namespace Sweeper\HelperPhp\Tool;

/**
 * 事件触发器
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/26 21:30
 * @Package \Sweeper\HelperPhp\Tool\Hooker
 */
abstract class Hooker
{

    private static $HOOKS = [];

    private function __construct() { }

    /**
     * 添加触发器
     * @param string   $key
     * @param callable $callback
     * @return boolean
     */
    public static function add(string $key, callable $callback): bool
    {
        self::$HOOKS[$key]   = self::$HOOKS[$key] ?? [];
        self::$HOOKS[$key][] = $callback;

        return true;
    }

    /**
     * 删除触发器
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/26 21:31
     * @param string   $key
     * @param callable $callback
     * @return bool
     */
    public static function delete(string $key, callable $callback): bool
    {
        if (self::$HOOKS[$key]) {
            $rst   = [];
            $found = false;
            foreach (self::$HOOKS[$key] as $item) {
                if ($item !== $callback) {
                    $rst[] = $item;
                } else {
                    $found = true;
                }
            }
            if ($found) {
                self::$HOOKS[$key] = $rst;

                return true;
            }
        }

        return false;
    }

    /**
     * 检测触发器是否存在
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/26 21:32
     * @param $key
     * @return false|int
     */
    public static function exists($key)
    {
        return (isset(self::$HOOKS[$key]) && self::$HOOKS[$key]) ? count(self::$HOOKS[$key]) : false;
    }

    /**
     * 触发事件
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/26 21:32
     * @param string $key
     * @return array|false
     */
    public static function fire(string $key/** , $param1, $param2 **/)
    {
        $args    = array_slice(func_get_args(), 1) ?: [];
        $returns = [];
        if (isset(self::$HOOKS[$key]) && self::$HOOKS[$key]) {
            foreach (self::$HOOKS[$key] as $item) {
                $result = call_user_func_array($item, $args);
                if ($result === false) {
                    return false;
                }
                $returns[] = $result;
            }
        }

        return $returns;
    }

}