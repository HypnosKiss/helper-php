<?php

namespace Sweeper\HelperPhp\Func;

use Closure;
use Sweeper\HelperPhp\Tool\Hooker;

/**
 * 输出最后调用堆栈
 */
function dump_last_exit_trace()
{
    declare(ticks = 1);
    $GLOBALS['___LAST_RUN___'] = null;
    register_tick_function(function() {
        $GLOBALS['___LAST_RUN___'] = debug_backtrace();
    });
    register_shutdown_function(function() {
        dump($GLOBALS['___LAST_RUN___'], 1);
    });
}

/**
 * 步进方式调试
 * @param int    $step 步长
 * @param string $fun  调试函数，默认使用dump
 * @deprecated PHP 7.2 已被官方禁用跨文件调用
 */
function tick_dump($step = 1, $fun = 'dump')
{
    register_tick_function($fun);
    eval("declare(ticks = $step);");
}

/**
 * 打印trace信息
 * @param      $trace
 * @param bool $with_callee
 * @param bool $with_index
 */
function print_trace($trace, $with_callee = false, $with_index = false)
{
    $ct = count($trace);
    foreach ($trace as $k => $item) {
        $callee = '';
        if ($with_callee) {
            $vs = [];
            foreach ($item['args'] as $arg) {
                $vs[] = var_export_min($arg, true);
            }
            $arg_statement = join(',', $vs);
            $arg_statement = substr(str_replace("\n", '', $arg_statement), 0, 50);
            $callee        = $item['class'] ? "\t{$item['class']}{$item['type']}{$item['function']}($arg_statement)" : "\t{$item['function']}($arg_statement)";
        }
        if ($with_index) {
            echo "[", ($ct - $k), "] ";
        }
        $loc = $item['file'] ? "{$item['file']} #{$item['line']} " : '';
        echo "{$loc}{$callee}", PHP_EOL;
    }
}

/**
 * 打印系统错误及trace跟踪信息
 * @param        $code
 * @param        $msg
 * @param        $file
 * @param        $line
 * @param string $trace_string
 */
function print_sys_error($code, $msg, $file = null, $line = null, $trace_string = '')
{
    echo "<pre>";
    $code = error2string($code);
    echo "[$code] $msg\n\n";
    echo "* $file #$line\n\n";

    if (!$trace_string) {
        $bs = debug_backtrace();
        array_shift($bs);
        foreach ($bs as $k => $b) {
            echo count($bs) - $k . " {$b['class']}{$b['type']}{$b['function']}\n";
            echo "  {$b['file']}  #{$b['line']} \n\n";
        }
    } else {
        echo $trace_string;
    }
    die;
}

/**
 * error code to string
 * @param $value
 * @return string
 */
function error2string($value)
{
    $level_names = [
        E_ERROR           => 'E_ERROR',
        E_WARNING         => 'E_WARNING',
        E_PARSE           => 'E_PARSE',
        E_NOTICE          => 'E_NOTICE',
        E_CORE_ERROR      => 'E_CORE_ERROR',
        E_CORE_WARNING    => 'E_CORE_WARNING',
        E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR      => 'E_USER_ERROR',
        E_USER_WARNING    => 'E_USER_WARNING',
        E_USER_NOTICE     => 'E_USER_NOTICE',
    ];
    if (defined('E_STRICT')) {
        $level_names[E_STRICT] = 'E_STRICT';
    }
    $levels = [];
    if (($value & E_ALL) == E_ALL) {
        $levels[] = 'E_ALL';
        $value    &= ~E_ALL;
    }
    foreach ($level_names as $level => $name) {
        if (($value & $level) == $level) {
            $levels[] = $name;
        }
    }

    return implode(' | ', $levels);
}

/**
 * string to error code
 * @param $string
 * @return int
 */
function string2error($string)
{
    $level_names = [
        'E_ERROR',
        'E_WARNING',
        'E_PARSE',
        'E_NOTICE',
        'E_CORE_ERROR',
        'E_CORE_WARNING',
        'E_COMPILE_ERROR',
        'E_COMPILE_WARNING',
        'E_USER_ERROR',
        'E_USER_WARNING',
        'E_USER_NOTICE',
        'E_ALL',
    ];
    if (defined('E_STRICT')) {
        $level_names[] = 'E_STRICT';
    }
    $value  = 0;
    $levels = explode('|', $string);

    foreach ($levels as $level) {
        $level = trim($level);
        if (defined($level)) {
            $value |= (int)constant($level);
        }
    }

    return $value;
}

/**
 * 时间打点标记，用于性能调试
 * @param string $tag
 * @param null   $data
 * @param array  $trace
 * @return array
 */
function performance_mark($tag = '', $data = null, $trace = [])
{
    $tm    = microtime(true);
    $mem   = memory_get_usage(true);
    $trace = $trace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

    global $c6trpVZUNR7G;
    $c6trpVZUNR7G[] = [$tm, $mem, $trace, $tag, $data];

    return $c6trpVZUNR7G;
}

/**
 * check is function
 * @param mixed $f
 * @return boolean
 */
function is_function($f)
{
    return (is_string($f) && function_exists($f)) || (is_object($f) && ($f instanceof Closure));
}

/**
 * get class(also trait) uses recursive
 * @param $class_or_object
 * @return array
 */
function class_uses_recursive($class_or_object)
{
    if (is_object($class_or_object)) {
        $class = get_class($class_or_object);
    } else {
        $class = $class_or_object;
    }
    $results = [];
    foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
        $results += trait_uses_recursive($class);
    }

    return array_unique($results);
}

/**
 * get trait uses recursive
 * @param $trait
 * @return array
 */
function trait_uses_recursive($trait)
{
    $traits = class_uses($trait);
    foreach ($traits as $trait) {
        $traits += trait_uses_recursive($trait);
    }

    return $traits;
}

/**
 * pdog
 * @param $fun
 * @param $handler
 */
function pdog($fun, $handler)
{
    declare(ticks = 1);
    register_tick_function(function() use ($fun, $handler) {
        $debug_list = debug_backtrace();
        foreach ($debug_list as $info) {
            if ($info['function'] == $fun) {
                call_user_func($handler, $info['args']);
            }
        }
    });
}

/**
 * get GUID
 * @return mixed
 */
function guid()
{
    global $__guid__;

    return $__guid__++;
}

/**
 * var_export in minimal format
 * @param      $var
 * @param bool $return
 * @return mixed|string
 */
function var_export_min($var, $return = false)
{
    if (is_array($var)) {
        $toImplode = [];
        foreach ($var as $key => $value) {
            $toImplode[] = var_export($key, true) . '=>' . var_export_min($value, true);
        }
        $code = 'array(' . implode(',', $toImplode) . ')';
        if ($return) {
            return $code;
        } else {
            echo $code;
        }
    } else {
        return var_export($var, $return);
    }
}
