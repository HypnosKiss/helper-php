<?php

namespace Sweeper\HelperPhp\Logger\Output;

use Sweeper\HelperPhp\Logger\Logger;
use Sweeper\HelperPhp\Logger\LoggerLevel;

/**
 * Browser console output
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/21 23:13
 * @Package \Sweeper\HelperPhp\Logger\Output\BrowserConsoleOutput
 */
class BrowserConsoleOutput extends CommonAbstract
{

    protected const LEVEL_MAP = [
        LoggerLevel::DEBUG     => 'debug',
        LoggerLevel::INFO      => 'info',
        LoggerLevel::WARNING   => 'warn',
        LoggerLevel::ERROR     => 'error',
        LoggerLevel::CRITICAL  => 'error',
        LoggerLevel::EMERGENCY => 'error',
    ];

    private $logs;

    public function __construct()
    {
        register_shutdown_function(function() {
            if (!$this->logs) {
                return;
            }
            echo '<script>';
            $default_id_css = 'color:#666; background-color:#ccc; border-radius:2px; padding:2px 0.5em; text-shadow:1px 1px 1px white; display:inline-block;';
            $cus_id_css     = 'color:#053c19; background-color:#00800036; border-radius:5px; padding:2px 0.5em; text-shadow:1px 1px 1px white; display:inline-block;';
            foreach ($this->logs as [$level, $messages, $logger_id, $trace_info]) {
                $op     = static::LEVEL_MAP[$level];
                $id_css = $logger_id === Logger::DEFAULT_LOG_ID ? $default_id_css : $cus_id_css;
                $json   = ["'%c$logger_id'", "'$id_css'"];
                foreach ($messages as $msg) {
                    $json[] = json_encode($msg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                }
                if ($trace_info) {
                    $callee = $trace_info['class'] . $trace_info['type'] . $trace_info['function'] . '()';
                    $loc    = $trace_info['file'] . "({$trace_info['line']})";
                    $json[] = json_encode($callee, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                    $json[] = json_encode($loc, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                }
                echo "console.$op(" . join(',', $json) . ");", PHP_EOL;
            }
            echo '</script>';
        });
    }

    public function output(array $messages, string $level, string $loggerId, array $traceInfo)
    {
        $this->logs[] = [$level, $messages, $loggerId, $traceInfo];
    }

}
