<?php

namespace Sweeper\HelperPhp\Tool;

use function Sweeper\HelperPhp\Func\format_size;

/**
 * 工具包
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:58
 * @Path \Sweeper\HelperPhp\Tool\TooKit
 */
class TooKit
{

    /**
     * 调试内存
     * @param mixed ...$args
     * @use declare(ticks = 2);TooKit::debugMemory();
     */
    public static function debugMemory(...$args): void
    {
        // declare(ticks = 1);// 需要设置
        // using a function as the callback
        $startTime = microtime(true);
        register_tick_function(function() use ($startTime) {
            echo microtime(true) - $startTime, ' ms, memory_usage: ', format_size(memory_get_usage()), '<br>', PHP_EOL;
        });
    }

}