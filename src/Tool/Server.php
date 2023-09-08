<?php

namespace Sweeper\HelperPhp\Tool;

use function Sweeper\HelperPhp\Func\format_size;
use function Sweeper\HelperPhp\Func\resolve_size;

/**
 * 服务器环境集成类
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/6 10:16
 * @Path \Sweeper\HelperPhp\Tool\Server
 */
class Server
{

    /**
     * User: Sweeper
     * Time: 2023/9/6 10:17
     * @return bool
     */
    public static function inWindows(): bool
    {
        return stripos(PHP_OS_FAMILY, 'WIN') === 0;
    }

    /**
     * User: Sweeper
     * Time: 2023/9/6 10:18
     * @return bool
     */
    public static function inCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * 服务器最大上传文件大小
     * 通过对比文件上传限制与post大小获取
     * @param bool $humanReadable 是否以可读方式返回
     * @return int
     */
    public static function getUploadMaxSize(bool $humanReadable = false): int
    {
        $upload_sz = trim(ini_get('upload_max_filesize'));
        $upload_sz = resolve_size($upload_sz);
        $post_sz   = trim(ini_get('post_max_size'));
        $post_sz   = resolve_size($post_sz);
        $ret       = min($upload_sz, $post_sz);
        if ($humanReadable) {
            return format_size($ret);
        }

        return $ret;
    }

    /**
     * get php core summary released info
     * @return string
     */
    public static function getPhpReleaseSummary(): string
    {
        $info     = self::getPhpInfo();
        $ts       = $info['phpinfo']['Thread Safety'] === 'enabled' ? 'ts' : 'nts';
        $compiler = $info['phpinfo']['Compiler'];
        if (preg_match('/ms(vc\d+)\s/i', $compiler, $matches)) {
            $compiler = strtolower($matches[1]);
        }

        return implode('-', [PHP_VERSION, $ts, $compiler, $info['phpinfo']['Architecture']]);
    }

    /**
     * 获取最大socket可用超时时间
     * @param int $ttf 允许提前时长
     * @return int 超时时间（秒），如为0，表示不限制超时时间
     */
    public static function getMaxSocketTimeout(int $ttf = 0): int
    {
        $max_execute_timeout = ini_get('max_execution_time') ?: 0;
        $max_socket_timeout  = ini_get('default_socket_timeout') ?: 0;
        $max                 = (!$max_execute_timeout || !$max_socket_timeout) ? max($max_execute_timeout, $max_socket_timeout) : min($max_execute_timeout, $max_socket_timeout);
        if ($ttf && $max) {
            return max($max - $ttf, 1); //最低保持1s，避免0值
        }

        return $max;
    }

    /**
     * get phpinfo() as array
     * @return array
     */
    public static function getPhpInfo(): array
    {
        static $phpinfo;
        if ($phpinfo) {
            return $phpinfo;
        }

        $entitiesToUtf8 = function($input) {
            return preg_replace_callback("/(&#[0-9]+;)/", function($m) {
                return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
            }, $input);
        };
        $plainText      = function($input) use ($entitiesToUtf8) {
            return trim(html_entity_decode($entitiesToUtf8(strip_tags($input))));
        };
        $titlePlainText = function($input) use ($plainText) {
            return '# ' . $plainText($input);
        };

        ob_start();
        phpinfo(-1);

        $phpinfo = ['phpinfo' => []];

        // Strip everything after the <h1>Configuration</h1> tag (other h1's)
        if (!preg_match('#(.*<h1[^>]*>\s*Configuration.*)<h1#s', ob_get_clean(), $matches)) {
            return [];
        }

        $input   = $matches[1];
        $matches = [];

        if (preg_match_all('#(?:<h2.*?>(?:<a.*?>)?(.*?)(?:<\/a>)?<\/h2>)|' . '(?:<tr.*?><t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>)?)?</tr>)#s', $input, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fn = strpos($match[0], '<th') === false ? $plainText : $titlePlainText;
                if (strlen($match[1])) {
                    $phpinfo[$match[1]] = [];
                } elseif (isset($match[3])) {
                    $keys1                                = array_keys($phpinfo);
                    $phpinfo[end($keys1)][$fn($match[2])] = isset($match[4]) ? [$fn($match[3]), $fn($match[4])] : $fn($match[3]);
                } else {
                    $keys1                  = array_keys($phpinfo);
                    $phpinfo[end($keys1)][] = $fn($match[2]);
                }
            }
        }

        return $phpinfo;
    }

}
