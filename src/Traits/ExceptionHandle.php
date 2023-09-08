<?php

namespace Sweeper\HelperPhp\Tool;

use Throwable;

use function Sweeper\HelperPhp\Func\var_export_min;

define('E_FATAL', E_ERROR|E_USER_ERROR|E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_PARSE);

/**
 * 通用异常处理
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:42
 * @Path \Sweeper\HelperPhp\Tool\ExceptionHandle
 */
trait ExceptionHandle
{

    /**
     * 获取调试回溯
     * @param array $trace
     * @param bool  $withCallee
     * @param bool  $withIndex
     * @param int   $substrLength
     * @return array
     */
    public static function getDebugBacktrace(array $trace = [], bool $withCallee = false, bool $withIndex = false, int $substrLength = 100): array
    {
        $traces = [];
        $trace  = $trace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $ct     = count($trace);
        foreach ($trace as $k => $item) {
            $callee = '';
            if ($withCallee) {
                $vs = [];
                foreach ($item['args'] ?: [] as $arg) {
                    $vs[] = var_export_min($arg, true);
                }
                $arg_statement = implode(',', $vs);
                $arg_statement = str_replace("\n", '', $arg_statement);
                $arg_statement = $substrLength ? substr($arg_statement, 0, $substrLength) : $arg_statement;
                $callee        = $item['class'] ? "\t{$item['class']}{$item['type']}{$item['function']}($arg_statement)" : "\t{$item['function']}($arg_statement)";
            }
            $loc   = $item['file'] ? "{$item['file']} #{$item['line']} " : '';
            $trace = ($withIndex ? "[" . ($ct - $k) . "] " : '') . "{$loc}{$callee}";
            if ($trace) {
                $traces[] = $trace;
            }
        }

        return $traces;
    }

    /**
     * 设置默认的异常处理程序，用于没有用 try/catch 块来捕获的异常。
     * 在 exception_handler 调用后异常会中止。
     * @param null $exceptionHandler
     */
    public static function registerExceptionHandle($exceptionHandler = null): void
    {
        $exceptionHandler = $exceptionHandler ?: function(Throwable $e) {
            $args['exception'] = static::formatErrorInfoToArray($e);
            $content           = date('Y-m-d H:i:s') . " Uncaught exception: " . json_encode($args, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
            $file_path         = sys_get_temp_dir() . '/exception';
            if (!file_exists($file_path) && !mkdir($file_path, 0777, true) && !is_dir($file_path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $file_path));
            }
            file_put_contents($file_path . '/exception.log', $content, FILE_APPEND);
        };
        set_exception_handler($exceptionHandler);
    }

    /**
     * 设置为用户定义的错误处理程序
     * @param            $errorHandler
     * @param int|string $error_types
     * @docUrl https://www.php.net/manual/zh/function.set-error-handler.php
     */
    public static function registerErrorHandle($errorHandler, $error_types = E_ALL|E_STRICT): void
    {
        set_error_handler($errorHandler ?: function($errno, $errstr, $errfile, $errline, $errcontext) {
            if (!(error_reporting()&$errno)) {
                // This error code is not included in error_reporting, so let it fall
                // through to the standard PHP error handler
                return false;
            }
            // $errstr may need to be escaped:
            // $errstr = htmlspecialchars($errstr);
            // $error  = "lvl: " . $error_level . " | msg:" . $error_message . " | file:" . $error_file . " | ln:" . $error_line;
            switch ($errno) {
                case E_USER_ERROR:
                    // echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
                    // echo "  Fatal error on line $errline in file $errfile";
                    // echo ", PHP ".PHP_VERSION." (".PHP_OS.")<br />\n";
                    // echo "Aborting...<br />\n";
                    exit(1);
                case E_USER_WARNING:
                    // echo "<b>My WARNING</b> error on line $errline in file $errfile [$errno] $errstr<br />\n";
                    break;
                case E_USER_NOTICE:
                    // echo "<b>My NOTICE</b> error on line $errline in file $errfile [$errno] $errstr<br />\n";
                    break;
                default:
                    // echo "Unknown error type: [$errno] $errstr<br />\n";
                    break;
            }

            /* Don't execute PHP internal error handler */
            // return true;
        }, $error_types);
    }

    /**
     * 注册要在脚本执行完成或调用exit()后执行的回调。可以对register_shutdown_function()进行多次调用，每次调用的顺序与注册的顺序相同。
     * 如果您在一个已注册的关闭函数中调用exit() ，则处理将完全停止并且不会调用其他已注册的关闭函数
     * You may get the idea to call debug_backtrace or debug_print_backtrace from inside a shutdown function, to trace where a fatal error occurred.
     * Unfortunately, these functions will not work inside a shutdown function.
     * @param            $shutdownHandler
     * @param int|string $error_types
     */
    public static function registerShutdownEvent($shutdownHandler = null, ...$parameter): void
    {
        $shutdownHandler = $shutdownHandler ?: function($parameter) {
            $last_error = error_get_last();
            ['type' => $error_type, 'message' => $error_message, 'file' => $error_file, 'line' => $error_line] = $last_error;
            if (!$last_error || !static::isFatalError($error_type)) {//没有错误 || 不是致命错误
                return false;
            }
            $error             = "[SHUTDOWN] lvl:" . static::showFriendlyErrorType($error_type) . " | msg:" . $error_message . " | file:" . $error_file . " | line:" . $error_line;
            $args['error']     = $error;
            $args['parameter'] = $parameter;
            $content           = date('Y-m-d H:i:s') . " Shutdown: " . json_encode($args, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . PHP_EOL;
            $file_path         = sys_get_temp_dir() . '/shutdown';
            if (!file_exists($file_path) && !mkdir($file_path, 0777, true) && !is_dir($file_path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $file_path));
            }
            file_put_contents($file_path . '/shutdown.log', $content, FILE_APPEND);
        };
        register_shutdown_function($shutdownHandler, $parameter);
    }

    /**
     * 致命错误
     * @param $error_type
     * @return bool
     */
    public static function isFatalError($error_type): bool
    {
        return in_array($error_type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR, E_USER_ERROR, E_PARSE], true);//$error["type"] === ($error["type"]&E_FATAL)
    }

    /**
     * 显示友好错误类型
     * @param $type
     * @return string
     */
    public static function showFriendlyErrorType($type): string
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }

        return "";
    }

    /**
     * 格式化错误信息
     * @param \Throwable $e
     * @return array
     */
    public static function formatErrorInfoToArray(Throwable $e): array
    {
        return [
            'message'      => $e->getMessage(),
            'file'         => $e->getFile(),
            'code'         => $e->getCode(),
            'line'         => $e->getLine(),
            'trace_string' => $e->getTraceAsString(),
        ];
    }

}