<?php

namespace Sweeper\HelperPhp\Tool;

use Lite\Component\Net\Client;

use function Sweeper\HelperPhp\Func\time_range_v;
use function Sweeper\HelperPhp\is_windows;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/24 13:32
 * @Package \Sweeper\HelperPhp\Tool\Console
 */
abstract class Console
{

    //前景色
    public const FORE_COLOR_BLACK        = '0;30';

    public const FORE_COLOR_DARK_GRAY    = '1;30';

    public const FORE_COLOR_BLUE         = '0;34';

    public const FORE_COLOR_LIGHT_BLUE   = '1;34';

    public const FORE_COLOR_GREEN        = '0;32';

    public const FORE_COLOR_LIGHT_GREEN  = '1;32';

    public const FORE_COLOR_CYAN         = '0;36';

    public const FORE_COLOR_LIGHT_CYAN   = '1;36';

    public const FORE_COLOR_RED          = '0;31';

    public const FORE_COLOR_LIGHT_RED    = '1;31';

    public const FORE_COLOR_PURPLE       = '0;35';

    public const FORE_COLOR_LIGHT_PURPLE = '1;35';

    public const FORE_COLOR_BROWN        = '0;33';

    public const FORE_COLOR_YELLOW       = '1;33';

    public const FORE_COLOR_LIGHT_GRAY   = '0;37';

    public const FORE_COLOR_WHITE        = '1;37';

    //前景色清单
    public const FORE_COLOR_MAP = [
        self::FORE_COLOR_BLACK,
        self::FORE_COLOR_DARK_GRAY,
        self::FORE_COLOR_BLUE,
        self::FORE_COLOR_LIGHT_BLUE,
        self::FORE_COLOR_GREEN,
        self::FORE_COLOR_LIGHT_GREEN,
        self::FORE_COLOR_CYAN,
        self::FORE_COLOR_LIGHT_CYAN,
        self::FORE_COLOR_RED,
        self::FORE_COLOR_LIGHT_RED,
        self::FORE_COLOR_PURPLE,
        self::FORE_COLOR_LIGHT_PURPLE,
        self::FORE_COLOR_BROWN,
        self::FORE_COLOR_YELLOW,
        self::FORE_COLOR_LIGHT_GRAY,
        self::FORE_COLOR_WHITE,
    ];

    //背景色
    public const BACK_COLOR_BLACK      = '40';

    public const BACK_COLOR_RED        = '41';

    public const BACK_COLOR_GREEN      = '42';

    public const BACK_COLOR_YELLOW     = '43';

    public const BACK_COLOR_BLUE       = '44';

    public const BACK_COLOR_MAGENTA    = '45';

    public const BACK_COLOR_CYAN       = '46';

    public const BACK_COLOR_LIGHT_GRAY = '47';

    //背景色清单
    public const BACK_COLOR_MAP = [
        self::BACK_COLOR_BLACK,
        self::BACK_COLOR_RED,
        self::BACK_COLOR_GREEN,
        self::BACK_COLOR_YELLOW,
        self::BACK_COLOR_BLUE,
        self::BACK_COLOR_MAGENTA,
        self::BACK_COLOR_CYAN,
        self::BACK_COLOR_LIGHT_GRAY,
    ];

    public const REQUIRED       = 'required';

    public const OPTIONAL       = 'optional';

    /**
     * get cli console color output string
     * @param string      $str
     * @param string|null $foregroundColor
     * @param string|null $backgroundColor
     * @return string
     */
    public static function getColorString(string $str, string $foregroundColor = null, string $backgroundColor = null): string
    {
        //windows console no support ansi color mode
        if (is_windows()) {
            return $str;
        }

        //linux cli
        $color_str = '';
        if ($foregroundColor) {
            $color_str .= "\033[" . $foregroundColor . "m";
        }
        if ($backgroundColor) {
            $color_str .= "\033[" . $backgroundColor . "m";
        }
        if ($color_str) {
            return $color_str . $str . "\033[0m";
        }

        return $str;
    }

    /**
     * get options
     * @param        $param
     *          <pre>
     *          get_options(
     *          array(
     *          '-s,-site-id' => array(static::OPTIONAL, 'require site id', 'def-site-id')
     *          ),
     *          'set site information'
     *          );
     *          </pre>
     * @param string $description
     * @param bool   $supportCgi
     * @return array|void
     * @throws \Exception
     */
    public static function getOptions(array $param, string $description = '', bool $supportCgi = true)
    {
        if (!$supportCgi && !Client::inCli()) {
            die('script only run in CLI mode');
        }

        //check short options error
        foreach ($_SERVER['argv'] ?: [] as $opt) {
            if (strpos($opt, '--') === false && strpos($opt, '-') === 0) {
                [$k, $v] = explode('=', $opt);
                if (preg_match('/\w\w+/', $v, $matches)) {
                    throw new \InvalidArgumentException("option value transited is ambiguity: [$k=$v]. Please use long option type");
                }
            }
        }

        $opt_str   = [];
        $long_opts = [];
        foreach ($param as $ks => $define) {
            [$required] = $define;
            foreach (explode(',', $ks) as $k) {
                if (strpos($k, '--') === 0) {
                    $long_opts[] = substr($k, 2) . ($required === static::REQUIRED ? ':' : '::');
                } elseif (strpos($k, '-') === 0) {
                    $opt_str[] = substr($k, 1) . ($required === static::REQUIRED ? ':' : '::');
                } else {
                    $opt_str[] = $k . ($required === static::REQUIRED ? ':' : '::');
                }
            }
        }

        $opt_str = implode('', $opt_str);

        //get options
        $opts = array_merge($_GET, getopt($opt_str, $long_opts) ?: []);

        $error = [];
        foreach ($param as $ks => $define) {
            [$required, $desc, $default] = $define;

            //found option value
            $found     = false;
            $found_val = null;
            foreach (explode(',', $ks) as $k) {
                $k = preg_replace('/^\-*/', '', $k);
                if (isset($opts[$k])) {
                    $found     = true;
                    $found_val = $opts[$k];
                    break;
                }
            }

            //set other keys
            if ($found) {
                foreach (explode(',', $ks) as $k) {
                    $k        = preg_replace('/^\-*/', '', $k);
                    $opts[$k] = $found_val;
                }
            }

            //no found
            if (!$found) {
                if ($required === static::REQUIRED) {
                    $error[] = "$ks require $desc";
                } //set default
                elseif (isset($default)) {
                    foreach (explode(',', $ks) as $k) {
                        $k        = preg_replace('/^\-*/', '', $k);
                        $opts[$k] = $default;
                    }
                }
            }
        }

        //handle error
        if ($error) {
            echo "\n[ERROR]:\n", join("\n", $error), "\n";
            echo "\n[ALL PARAMETERS]:\n";
            foreach ($param as $k => $define) {
                [$required, $desc] = $define;
                echo "$k\t[$required] $desc\n";
            }

            if ($description) {
                echo "\n[DESCRIPTION]:\n";
                $call = debug_backtrace(null, 1);
                $f    = basename($call[0]['file']);
                echo "$f $description\n";
            }
            echo "\n[DEBUG]\n";
            debug_print_backtrace();
            exit;
        }

        //rebuild array
        foreach ($opts as $k => $val) {
            if (preg_match_all('/\[([^\]+])\]/', $k, $matches)) {
                unset($opts[$k]);
                parse_str("$k=$val", $tmp);
                $opts = array_merge_recursive($opts, $tmp);
            }
        }

        return $opts;
    }

    /**
     * 获取任务进度描述文本，格式为：
     * 当前函数为独占函数
     * @param int    $currentIndex 当前处理序号
     * @param int    $total        总数
     * @param string $format       格式表达
     * @return string
     */
    public static function getTasksProgressText(int $currentIndex, int $total, string $format = "\n%NOW_DATE %NOW_TIME [PG:%PROGRESS RT:%REMAINING_TIME]"): string
    {
        static $startTime;
        if (!$startTime) {
            $startTime = time();
        }

        $now_date = date('Y/m/d');
        $now_time = date('H:i:s');
        $progress = "$currentIndex/$total";

        $remaining_time = '-';
        if ($currentIndex) {
            $rt             = (time() - $startTime) * ($total - $currentIndex) / $currentIndex;
            $remaining_time = time_range_v($rt);
        }

        return str_replace(['%NOW_DATE', '%NOW_TIME', '%PROGRESS', '%REMAINING_TIME'], [$now_date, $now_time, $progress, $remaining_time], $format);
    }

    /**
     * @param string $cmdLine
     * @param array  $param
     * @return string
     */
    public static function buildCommand(string $cmdLine, array $param = []): string
    {
        foreach ($param as $k => $val) {
            if (is_array($val)) {
                foreach ($val as $i => $vi) {
                    $vi      = escapeshellarg($vi);
                    $cmdLine .= " --{$k}[{$i}]={$vi}";
                }
            } else {
                if (strlen($k) > 0) {
                    $val     = escapeshellarg($val);
                    $cmdLine .= " --$k=$val";
                } else {
                    $val     = escapeshellarg($val);
                    $cmdLine .= " -$k=$val";
                }
            }
        }

        return $cmdLine;
    }

    /**
     * 运行命令，并获取命令输出（直至进程结束）
     * @param string $command
     * @param array  $param
     * @return null|string
     * @throws \Exception
     */
    public static function runCommand(string $command, array $param = []): ?string
    {
        $descriptors_pec = [
            0 => ["pipe", "r"],   // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],   // stdout is a pipe that the child will write to
            2 => ["pipe", "w"],    // stderr is a pipe that the child will write to
        ];
        flush();
        //WINDOWS环境：必须传递 $_SERVER给子进程，否则子进程内数据库连接可能出错 ？？
        $command = static::buildCommand($command, $param);
        $pipes   = [];
        $process = @proc_open($command, $descriptors_pec, $pipes, realpath('./'), $_SERVER);// $_SERVER 会导致 Array to string conversion
        if ($process === false || $process === null) {
            throw new \RuntimeException('Process create fail:' . $command);
        }
        if (is_resource($process)) {
            $result_str = $error_str = '';
            while ($s = fgets($pipes[1])) {
                $result_str .= $s;
            }
            $has_error = false;
            while ($e = fgets($pipes[2])) {
                $has_error = true;
                $error_str .= $e;
            }

            return $has_error ? $error_str : $result_str;
        }
        proc_close($process);

        return null;
    }

    public static function debug(): void
    {
        if (PHP_SAPI !== 'cli') {
            static $fst;
            if (!$fst++) {
                echo '<pre>';
            }
        }

        $args = func_get_args();
        echo "\n" . date("H:i:s ");
        foreach ($args as $arg) {
            if (is_string($arg) || is_numeric($arg)) {
                echo $arg;
            } else {
                if (is_bool($arg)) {
                    echo $arg ? '[true]' : '[false]';
                } else {
                    if (is_null($arg)) {
                        echo '[null]';
                    } else {
                        echo preg_replace('/\s*\\n\s*/', '', var_export($arg, true));
                    }
                }
            }
            echo "\t";
        }
        ob_flush();
        flush();
    }

    public static function log(string $string, $timePreset = 'Y-m-d H:i:s'): void
    {
        echo "\n", ($timePreset ? date($timePreset) . "\t " : '') . $string;
    }

    public static function error(string $string, $timePreset = 'Y-m-d H:i:s'): void
    {
        echo "\n", ($timePreset ? date($timePreset) . "\t " : '') . static::getColorString("[ERROR] $string", static::FORE_COLOR_RED);
    }

}
