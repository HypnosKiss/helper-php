<?php

namespace Sweeper\HelperPhp\Logger\Output;

use Psr\Log\AbstractLogger;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/21 23:12
 * @Package \Sweeper\HelperPhp\Logger\Output\CommonAbstract
 */
abstract class CommonAbstract extends AbstractLogger
{

    /**
     * parse log
     * User: Sweeper
     * Time: 2023/7/21 14:50
     * @param       $message
     * @param array $context
     * @return mixed|string|null
     */
    public function parseLog($message = null, array $context = [])
    {
        if (is_string($message) && !empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }
            $message = strtr($message, $replace);
        }

        return $message;
    }

    public function log($level, $message, array $context = [], string $loggerId = null, array $traceInfo = [])
    {
        $this->output((array)$this->parseLog($message, $context), $level, $loggerId, $traceInfo);
    }

    /**
     * Merge messages
     * User: Sweeper
     * Time: 2023/7/21 14:50
     * @param $messages
     * @return string
     */
    public static function combineMessages($messages): string
    {
        return count($messages) === 1 && is_string(current($messages)) ? current($messages) : json_encode((array)$messages, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    /**
     * print trace info
     * @param array $traceInfo trace info from debug_backtrace()
     * @param bool  $withFunc  output with class or function name
     * @param bool  $asReturn  output as return only
     * @return string
     */
    public static function printTraceInfo(array $traceInfo, bool $withFunc = false, bool $asReturn = false): string
    {
        $loc = '';
        if ($withFunc) {
            $loc .= $traceInfo['class'] . $traceInfo['type'] . $traceInfo['function'] . '() ';
        }
        $loc .= $traceInfo['file'] . "#{$traceInfo['line']}";
        if (!$asReturn) {
            echo $loc;
        }

        return $loc;
    }

    /**
     * format log message as single line text
     * @param $messages
     * @param $level
     * @param $loggerId
     * @param $traceInfo
     * @return string
     */
    public static function formatAsText($messages, $level, $loggerId, $traceInfo): string
    {
        $text = date('Y-m-d H:i:s') . ($traceInfo ? '' : ' ' . $loggerId) . " [$level] " . static::combineMessages($messages);
        if ($traceInfo) {
            $text .= ' ' . static::printTraceInfo($traceInfo, false, true);
        }

        return $text;
    }

    /**
     * output called as function
     * @param array|string $messages
     * @param string       $level
     * @param string       $loggerId
     * @param array        $traceInfo
     * @return mixed
     */
    public function __invoke($messages, string $level, string $loggerId, array $traceInfo = [])
    {
        return $this->output($messages, $level, $loggerId, $traceInfo);
    }

    /**
     * output handler
     * User: Sweeper
     * Time: 2023/9/17 22:46
     * @param array  $messages
     * @param string $level
     * @param string $loggerId
     * @param array  $traceInfo
     * @return mixed
     */
    abstract public function output(array $messages, string $level, string $loggerId, array $traceInfo);

}
