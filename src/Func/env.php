<?php

namespace Sweeper\HelperPhp\Func;

function server_in_windows()
{
    return stripos(PHP_OS_FAMILY, 'WIN') === 0;
}

function get_upload_max_size($human_readable = false)
{
    $upload_sz = trim(ini_get('upload_max_filesize'));
    $upload_sz = resolve_size($upload_sz);
    $post_sz   = trim(ini_get('post_max_size'));
    $post_sz   = resolve_size($post_sz);
    $ret       = min($upload_sz, $post_sz);
    if ($human_readable) {
        return format_size($ret);
    }

    return $ret;
}

/**
 * @return array
 */
function get_php_info(): array
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
                $phpinfo[end($keys1)][$fn($match[2])] = isset($match[4]) ? [
                    $fn($match[3]),
                    $fn($match[4]),
                ] : $fn($match[3]);
            } else {
                $keys1                  = array_keys($phpinfo);
                $phpinfo[end($keys1)][] = $fn($match[2]);
            }
        }
    }

    return $phpinfo;
}

/**
 * get console text colorize
 * @param      $text
 * @param null $fore_color
 * @param null $back_color
 * @return string
 */
function console_color($text, $fore_color = null, $back_color = null): string
{
    static $fore_color_map = [
        'default'      => '0:39',
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
    ], $back_color_map = [
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47',
    ];
    $color_str = '';
    if ($fore_color) {
        $color_str .= "\033[" . $fore_color_map[$fore_color] . "m";
    }
    if ($back_color) {
        $color_str .= "\033[" . $back_color_map[$back_color] . "m";
    }
    if ($color_str) {
        return $color_str . $text . "\033[0m";
    }

    return $text;
}

/**
 * show progress in console
 * @param int      $index
 * @param int      $total
 * @param string   $patch_text 补充显示文本
 * @param int|null $start_time 开始时间戳
 * @param int      $progress_length
 * @param int      $max_length
 */
function show_progress(int $index, int $total, string $patch_text = '', int $start_time = null, int $progress_length = 50, int $max_length = 0)
{
    $pc      = round(100 * $index / $total);
    $reminds = '';
    if (!$start_time) {
        static $inner_start_time;
        if (!$inner_start_time) {
            $inner_start_time = time();
        }
        $start_time = $inner_start_time;
    }
    if ($index) {
        $reminds = ' in ' . format_time_size((time() - $start_time) * ($total - $index) / $index);
    }
    $fin_chars  = round(($index / $total) * $progress_length);
    $left_chars = $progress_length - $fin_chars;

    $str        = "\r$index/$total $pc% [" . str_repeat('=', $fin_chars) . str_repeat('.', $left_chars) . "]{$reminds} $patch_text";
    $max_length = $max_length ?: strlen($str) + 10;
    $str        = str_pad($str, $max_length, ' ', STR_PAD_RIGHT);
    echo $str;
    if ($index >= $total) {
        echo PHP_EOL;
    }
}