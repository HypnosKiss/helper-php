<?php

namespace Sweeper\HelperPhp;

use function Sweeper\HelperPhp\Func\array_clear_empty;
use function Sweeper\HelperPhp\Func\format_size;
use function Sweeper\HelperPhp\Func\resolve_size;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/19 22:45
 */

const ONE_SECOND      = 1;
const ONE_MINUTE      = 60;
const ONE_HOUR        = 3600;
const ONE_DAY         = 86400;
const ONE_WEEK        = 604800;
const ONE_MONTH       = 2592000;
const ONE_YEAR        = 31536000;
const DATETIME_FORMAT = 'Y-m-d H:i:s';

if (!function_exists('flatten_array')) {
    /**
     * 展平阵列 - 多维数组转一维数组
     * User: Sweeper
     * Time: 2023/7/3 17:07
     * @param $array
     * @return mixed|null
     */
    function flatten_array($array)
    {
        return array_reduce($array, static function($result, $item) {
            if (is_array($item)) {
                return array_merge($result, flatten_array($item));
            }
            $result[] = $item;

            return $result;
        }, []);
    }
}

if (!function_exists('multidimensional_array_merge')) {
    /**
     * 多维数组合并
     * User: Sweeper
     * Time: 2023/7/3 17:19
     * @param array $multidimensionalArray
     * @return array
     */
    function multidimensional_array_merge(array $multidimensionalArray): array
    {
        $array = [];
        array_walk_recursive($multidimensionalArray, static function($val, $key) use (&$array) {
            $array[$key] = $val;
        });

        return $array;
    }
}

if (!function_exists('get_specify_struct')) {
    /**
     * 获取指定结构数据
     * User: Sweeper
     * Time: 2023/8/21 15:09
     * @param array  $input
     * @param string $valueKey
     * @param string $labelKey
     * @return array
     */
    function get_specify_struct(array $input, string $valueKey = 'value', string $labelKey = 'label'): array
    {
        array_walk($input, static function(&$val, $key) use ($valueKey, $labelKey) {
            $val = [
                $valueKey => $key,
                $labelKey => $val,
            ];
        });

        return $input;
    }
}

if (!function_exists('throw_exception')) {
    /**
     * 抛出异常
     * User: Sweeper
     * Time: 2023/8/21 15:10
     * @param string $msg
     * @param int    $code
     * @param string $throwable
     * @return mixed
     */
    function throw_exception(string $msg = '', int $code = 400, string $throwable = \RuntimeException::class)
    {
        if (!empty($throwable) && ($throwableException = new $throwable($msg, $code)) instanceof \Throwable) {
            throw $throwableException;
        }
        throw new \RuntimeException($msg, $code);
    }
}

if (!function_exists('truncation_words')) {
    function truncation_words(string $string, int $wordLimit = 255, $delimiter = ' ', $search = [PHP_EOL, "\r\n", "\n"]): string
    {
        if (mb_strlen($string) > $wordLimit) {
            $stringLimit = mb_substr(str_replace($search, $delimiter, $string), 0, $wordLimit);

            return substr($stringLimit, 0, strrpos($stringLimit, $delimiter));
        }

        return $string;
    }
}

if (!function_exists('extract_words')) {
    function extract_words(string $string, int $wordLimit = 30, $delimiter = ' '): string
    {
        $words = explode($delimiter, $string);

        return implode($delimiter, array_splice($words, 0, $wordLimit));
    }
}

if (!function_exists('camelize')) {
    /**
     * 下划线转驼峰
     * 思路:
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     * User: Sweeper
     * Time: 2023/8/21 15:17
     * @param        $string
     * @param string $separator
     * @return string|string[]
     */
    function camelize($string, string $separator = '_')
    {
        //原字符串中的分隔符用空格替换
        $string = str_replace($separator, ' ', $string);

        //字符串中每个单词的首字母转换为大写
        $string = ucwords($string);

        //去空格
        return str_replace(' ', '', $string);// return ltrim(str_replace(' ', '', ucwords($separator . str_replace($separator, ' ', strtolower($string)))), $separator);
    }
}

if (!function_exists('un_camelize')) {
    /**
     * 驼峰命名转下划线命名 思路: 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     * User: Sweeper
     * Time: 2023/8/21 15:18
     * @param        $string
     * @param string $separator
     * @return string
     */
    function un_camelize($string, string $separator = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . $separator . '$2', $string));
    }
}

if (!function_exists('get_ip')) {
    /**
     * 获取用户IP地址
     * User: Sweeper
     * Time: 2023/8/21 15:19
     * @return mixed|string
     */
    function get_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $cip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $cip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } else {
            $cip = '';
        }

        preg_match('/[\d\.]{7,15}/', $cip, $matches);
        $cip = $matches[0] ?? 'unknown';
        unset($matches);

        return $cip;
    }
}

if (!function_exists('get_browser')) {
    /**
     * 获取访问的浏览器
     * User: Sweeper
     * Time: 2023/8/21 15:21
     * @return string
     */
    function get_browser(): string
    {
        $browser = '';
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            $browser = 'robot!';
        } elseif ((false === strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)) {
            $browser = 'Internet Explorer 11.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) {
            $browser = 'Internet Explorer 10.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            $browser = 'Internet Explorer 9.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            $browser = 'Internet Explorer 8.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            $browser = 'Internet Explorer 7.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            $browser = 'Internet Explorer 6.0';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Edge')) {
            $browser = 'Edge';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
            $browser = 'Firefox';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $browser = 'Chrome';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
            $browser = 'Safari';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
            $browser = 'Opera';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], '360SE')) {
            $browser = '360SE';
        } elseif (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessage')) {
            $browser = 'MicroMessage';//微信浏览器
        }

        return $browser;
    }
}

if (!function_exists('array_join')) {
    /**
     * 数组连接为字符
     * User: Sweeper
     * Time: 2023/8/21 15:23
     * @param array $array
     * @return string
     */
    function array_join(array $array): string
    {
        return "'" . implode("','", $array) . "'";
    }
}

if (!function_exists('is_index_array')) {
    /**
     * 是索引数组
     * User: Sweeper
     * Time: 2023/8/21 15:28
     * @param array $arr
     * @return bool
     */
    function is_index_array(array $arr): bool
    {
        return array_keys($arr) === array_keys(array_values($arr));
    }
}

if (!function_exists('format_seconds')) {
    /**
     * 将秒转换为XX天XX小时XX分钟XX秒显示
     * User: Sweeper
     * Time: 2023/8/21 15:30
     * @param $sec
     * @return array
     */
    function format_seconds($sec): array
    {
        $result            = [];
        $result['days']    = floor($sec / (24 * 3600));
        $sec               %= (24 * 3600);
        $result['hours']   = floor($sec / 3600);
        $remainSeconds     = $sec % 3600;
        $result['minutes'] = floor($remainSeconds / 60);
        $result['seconds'] = (int)($sec - $result['hours'] * 3600 - $result['minutes'] * 60);

        return $result;
    }
}

if (!function_exists('time_to_text')) {
    /**
     * 将时间秒数转化为“天/小时/分/秒”
     * User: Sweeper
     * Time: 2023/8/21 15:33
     * @param $time
     * @return string
     */
    function time_to_text($time): string
    {
        if (0 >= $time) {
            return (0 . '秒');
        }

        if (60 > $time) {    // 秒
            return $time . '秒';
        }

        if (3600 > $time) {    // 分
            return (int)($time / 60) . '分' . time_to_text($time % 60);
        }

        if (3600 * 24 > $time) {    // 时
            return (int)($time / 3600) . '时' . time_to_text($time % 3600);
        }

        return (int)($time / (3600 * 24)) . '天' . time_to_text($time % (3600 * 24));
    }
}

if (!function_exists('time_diff')) {
    /**
     * 时间差计算
     * User: Sweeper
     * Time: 2023/7/14 16:40
     * @param string $startTime
     * @param string $endTime
     * @return string[]
     * @throws Exception
     */
    function time_diff(string $startTime, string $endTime): array
    {
        $datetimeStart = new \DateTime($startTime);
        $datetimeEnd   = new \DateTime($endTime);
        $dateInterval  = $datetimeStart->diff($datetimeEnd);

        return [
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'days'         => $dateInterval->days,// 间隔跨越的总天数
            'y'            => $dateInterval->y,// 年数
            'm'            => $dateInterval->m,// 月数
            'd'            => $dateInterval->d,// 天数
            'h'            => $dateInterval->h,// 小时数
            'i'            => $dateInterval->i,// 分钟数
            's'            => $dateInterval->s,// 秒数
            'f'            => $dateInterval->f,// 微秒数
            'invert'       => $dateInterval->invert,// 如果间隔反转，则为 1，否则为 0
            'dateInterval' => $dateInterval,// \DateInterval 对象
        ];
    }
}

if (!function_exists('html_to_text')) {
    /**
     * 转换html为txt文本的函数
     * User: Sweeper
     * Time: 2023/8/21 15:34
     * @param        $str
     * @param string $encode
     * @return string|string[]|null
     */
    function html_to_text($str, string $encode = 'UTF-8')
    {
        $str       = preg_replace('/<style .*?<\/style>/is', '', $str);
        $str       = preg_replace('/<script .*?<\/script>/is', '', $str);
        $str       = preg_replace('/<br \s*\/?\/>/i', "\n", $str);
        $str       = preg_replace('/<br>/i', "\n", $str);
        $str       = preg_replace('/<br\/>/i', "\n", $str);
        $str       = preg_replace('/<\/p>/i', "\n", $str);
        $str       = preg_replace('/<\/td>/i', "\n", $str);
        $str       = preg_replace('/<\/div>/i', "\n", $str);
        $str       = preg_replace('/<\/blockquote>/i', "\n", $str);
        $str       = preg_replace('/<\/li>/i', "\n", $str);
        $str       = preg_replace('/\&nbsp\;/i', ' ', $str);
        $str       = preg_replace('/\&nbsp/i', ' ', $str);
        $str       = preg_replace('/\&amp\;/i', '&', $str);
        $str       = preg_replace('/\&amp/i', '&', $str);
        $str       = preg_replace('/\&ldquo\;/i', '"', $str);
        $str       = preg_replace('/\&ldquo/i', '"', $str);
        $str       = preg_replace('/\&lsquo\;/i', "'", $str);
        $str       = preg_replace('/\&lsquo/i', "'", $str);
        $str       = preg_replace('/\&rsquo\;/i', "'", $str);
        $str       = preg_replace('/\&rsquo/i', "'", $str);
        $str       = preg_replace('/\&gt\;/i', '>', $str);
        $str       = preg_replace('/\&gt/i', '>', $str);
        $str       = preg_replace('/\&rdquo\;/i', '"', $str);
        $str       = preg_replace('/\&rdquo/i', '"', $str);
        $allowtags = 'img|font|div|table|tbody|tr|td|th|br|p|b|strong|i|u|em|span|ol|ul|li';
        $str       = preg_replace("/<(\/?($allowtags).*?)>/is", '', $str);
        $str       = htmlspecialchars($str);
        $str       = strip_tags($str);
        $str       = html_entity_decode($str, ENT_QUOTES, $encode);

        return preg_replace('/\&\#.*?\;/i', '', $str);
    }
}

if (!function_exists('array_merge_recursive_one_group')) {
    /**
     * 递归合并多维数组成一维数组
     * User: Sweeper
     * Time: 2023/8/21 15:38
     * @param $array
     * @return array
     */
    function array_merge_recursive_one_group($array): array
    {
        $new_array = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $new_array = array_merge($new_array, array_merge_recursive_one_group($item));
            } else {
                $new_array[] = $item;
            }
        }

        return array_clear_empty($new_array);
    }
}

if (!function_exists('include_chinese')) {
    /**
     * 检测是否包含中文
     * @param string $str
     * @return bool
     */
    function include_chinese(string $str): bool
    {
        return $str && preg_match('/[\x{4e00}-\x{9fa5}]/u', $str);
    }
}

if (!function_exists('array_unset_by_key')) {
    /**
     * 根据数组KEY,删除数组
     * @param array  $arr       数据
     * @param string $del_key   指定删除字段
     * @param bool   $recursive 是否递归处理，如果递归，则arr允许为任意维数组
     * @param bool   $match     是否模糊匹配键值
     * @return array
     */
    function array_unset_by_key(array $arr, string $del_key, bool $recursive = true, bool $match = false): array
    {
        if (!$arr || !is_array($arr)) {
            return $arr;
        }
        foreach ($arr as $k => &$item) {
            if (($k === $del_key && !$match) || (stripos($k, $del_key) !== false && $match)) {
                unset($arr[$k]);
            } elseif ($recursive && is_array($item)) {
                $item = array_unset_by_key($item, $del_key, $recursive, $match);
            }
        }

        return $arr;
    }
}

if (!function_exists('array_push_by_path_custom')) {
    /**
     * 根据xpath，将数据压入数组 替换原eval方式
     * 原eval 方法 Lite\func\array_push_by_path;
     * @param array        $data
     * @param string|array $path 路径 示例a.b a.0 a[0] a["c"]
     * @param              $value
     * @param string       $glue 分割符 默认.
     */
    function array_push_by_path_custom(array &$data, $path, $value, string $glue = '.')
    {
        if (!is_array($path)) {
            //兼容a[0] a["c"] 替换为a.0 a.c
            if (strpos($path, '[') !== false) {
                $path = str_replace(['\'', '"', ']', '['], ['', '', '', $glue], $path);
            }
            $path = explode($glue, $path);
        }
        if (count($path) === 1) {
            $first        = $path[0];
            $data[$first] = $value;
        } else {
            $first = array_shift($path);
            array_push_by_path_custom($data[$first], $path, $value);
        }
    }
}

if (!function_exists('base64_encode_image')) {
    /**
     * 图片转base64
     * @param string $imageFile 图片路径
     * @return string
     */
    function base64_encode_image(string $imageFile): string
    {
        $base64_image = '';
        if (file_exists($imageFile) || is_file($imageFile)) {
            $image_info   = getimagesize($imageFile);
            $image_data   = fread(fopen($imageFile, 'rb'), filesize($imageFile));
            $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        }

        return $base64_image;
    }
}

if (!function_exists('object_to_array')) {
    /**
     * 对象转数组
     * @param $obj
     * @return array
     */
    function object_to_array($obj): array
    {
        $arr  = [];
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val       = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }

        return $arr;
    }
}

if (!function_exists('pretty_time_second')) {
    /**
     * 美化时间
     * User: Sweeper
     * Time: 2023/8/21 16:02
     * @param      $second
     * @param bool $zh
     * @return int|string
     */
    function pretty_time_second($second, bool $zh = true)
    {
        $output = '';
        $tks    = [
            ONE_YEAR   => ['Year', '年', '年'],
            ONE_MONTH  => ['Month', '月', '个月'],
            ONE_WEEK   => ['Week', '周', '周'],
            ONE_DAY    => ['Day', '天', '天'],
            ONE_HOUR   => ['Hour', '时', '小时'],
            ONE_MINUTE => ['Minute', '分', '分钟'],
            ONE_SECOND => ['Second', '秒', '秒'],
        ];
        foreach ($tks as $sec => [$en_desc, $zh_desc, $integerDesc]) {
            if ($second >= $sec) {
                $output .= floor($second / $sec) . ($second % $sec === 0 ? $integerDesc : ($zh ? $zh_desc : $en_desc));
            }
            $second %= $sec;
        }
        $output === '' && $output = 0;

        return $output;
    }
}

if (!function_exists('call_class_method')) {
    /**
     * 调用指定类方法
     * @param       $className
     * @param       $methodName
     * @param mixed ...$parameter
     * @return mixed
     * @throws \ReflectionException
     */
    function call_class_method($className, $methodName, array $parameter)
    {
        $refClass = new \ReflectionClass($className); //通过类名进行反射
        $instance = $refClass->newInstance();         //通过反射类进行实例化
        $method   = $refClass->getmethod($methodName);//通过方法名获取指定方法
        $method->setAccessible(true);                 //设置可访问性

        return $method->invokeArgs($instance, $parameter);//执行方法
    }
}

if (!function_exists('get_class_property_val')) {
    /**
     * 获取指定类的属性值
     * @param $className
     * @param $propertyName
     * @return mixed
     * @throws \ReflectionException
     */
    function get_class_property_val($className, $propertyName)
    {
        // 获取反射类及反射属性
        $refClass = new \ReflectionClass($className);     //通过类名进行反射
        $instance = $refClass->newInstance();             //通过反射类进行实例化
        $property = $refClass->getProperty($propertyName);//通过方法名获取指定方法
        $property->setAccessible(true);                   //设置可访问性

        return $property->getValue($instance);            //获取属性
    }
}

if (!function_exists('json_last_error_msg')) {
    /**
     * JSON 最后一个错误消息
     * User: Sweeper
     * Time: 2023/2/24 12:31
     * json_last_error_msg(): string 成功则返回错误信息，如果没有错误产生则返回 "No error" 。
     * @return string
     */
    function json_last_error_msg(): string
    {
        static $ERRORS = [
            JSON_ERROR_NONE           => '',// No error
            JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR      => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX         => 'Syntax error',
            JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        ];

        $error = json_last_error();

        return $ERRORS[$error] ?? 'Unknown error';
    }
}

if (!function_exists('get_json_last_error')) {
    /**
     * 返回 JSON 编码解码时最后发生的错误。
     * User: Sweeper
     * Time: 2023/2/24 13:44
     * @return string
     */
    function get_json_last_error(): string
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = '';// No error
                break;
            case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = ' - Unknown error';
                break;
        }

        return $error;
    }
}

if (!function_exists('is_multiple_array')) {
    /**
     * 是多维数组
     * User: Sweeper
     * Time: 2023/8/21 10:48
     * @param $array
     * @return bool
     */
    function is_multiple_array($array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }
}

if (!function_exists('array_key_exists_ignore_case')) {
    /**
     * 忽略大小写返回指定KEY的值
     * User: Sweeper
     * Time: 2023/8/21 11:44
     * @param      $array
     * @param      $specifyKey
     * @param bool $recursive
     * @param int  $case
     * @return array|mixed|string
     */
    function array_key_exists_ignore_case($array, $specifyKey = null, bool $recursive = true, int $case = CASE_LOWER)
    {
        $result   = [];
        $caseFunc = $case === CASE_LOWER ? 'strtolower' : 'strtoupper';
        if ($recursive === true && count($array) !== count($array, COUNT_RECURSIVE)) {
            foreach ($array as $key => $_array) {
                $result[strtolower($key)] = array_key_exists_ignore_case($_array, null, true, $case);
            }
        } else {
            $result = array_change_key_case($array, $case);
        }
        if ($specifyKey = $caseFunc($specifyKey)) {
            return $result[$specifyKey] ?? '';
        }

        return $result;
    }
}

if (!function_exists('generate_random_string')) {

    /**
     * 生成随机字符串
     * User: Sweeper
     * Time: 2023/3/21 16:08
     * @param int $length
     * @return string
     */
    function generate_random_string(int $length = 5): string
    {
        $arr = array_merge(range('a', 'b'), range('A', 'B'), range('0', '9'));
        shuffle($arr);
        $arr = array_flip($arr);
        $arr = array_rand($arr, $length);

        return implode('', $arr);
    }
}

if (!function_exists('get_microtime')) {
    /**
     * 获取毫秒时间戳
     * User: Sweeper
     * Time: 2023/3/21 16:10
     * @return float
     */
    function get_microtime(): float
    {
        [$millisecond, $second] = explode(' ', microtime());

        return (float)sprintf('%.0f', ((float)$millisecond + (float)$second) * 1000);
    }
}

if (!function_exists('str_to_utf8')) {
    /**
     * 字符串转 UTF-8
     * User: Sweeper
     * Time: 2023/9/6 8:55
     * @param          $str
     * @param string[] $encodingList
     * @param string   $toEncoding
     * @return mixed|string
     */
    function str_to_utf8($str, array $encodingList = ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'], string $toEncoding = 'UTF-8')
    {
        $encode = mb_detect_encoding($str, $encodingList);
        if ($encode === $toEncoding) {
            return $str;
        }

        return mb_convert_encoding($str, $toEncoding, $encode);
    }
}

if (!function_exists('uuid')) {
    /**
     * 生成唯一标识（32位）
     * User: Sweeper
     * Time: 2023/8/21 16:13
     * @param string $prefix
     * @return string
     */
    function uuid(string $prefix = ''): string
    {
        $chars = md5(uniqid($prefix . get_microtime() . mt_rand(), true));

        return substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-' . substr($chars, 12, 4) . '-' . substr($chars, 16, 4) . '-' . substr($chars, 20, 12);
    }
}

if (!function_exists('encrypt')) {
    /**
     * User: Sweeper
     * Time: 2023/8/21 16:17
     * @param string $data
     * @param string $secretKey
     * @param string $iv
     * @param string $method
     * @return false|string
     */
    function encrypt(string $data, string $secretKey, string $iv, string $method = 'aes-256-cbc')
    {
        if (empty($data)) {
            return $data;
        }

        return openssl_encrypt($data, $method, $secretKey, OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('decrypt')) {
    /**
     * 解密
     * User: Sweeper
     * Time: 2023/8/21 16:20
     * @param string $data
     * @param string $secretKey
     * @param string $iv
     * @param string $method
     * @return false|string
     */
    function decrypt(string $data, string $secretKey, string $iv, string $method = 'aes-256-cbc')
    {
        if (empty($data)) {
            return $data;
        }

        return openssl_decrypt($data, 'aes-256-cbc', $secretKey, OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('xml_to_array')) {
    function xml_to_array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}

if (!function_exists('trim_array')) {

    //数组去空格
    function trim_array($Input)
    {
        if (!is_array($Input)) {
            return trim($Input);
        }

        return array_map('trim_array', $Input);
    }
}

if (!function_exists('string_to_array')) {

    /**
     * 字符串转数组
     * User: Sweeper
     * Time: 2023/9/6 15:05
     * @param string $str
     * @param array  $search
     * @param string $glue
     * @param bool   $filter
     * @return array|false|string[]
     */
    function string_to_array(string $str, array $search = [PHP_EOL, ',', '，', ' ', "\r\n", "\r", "\n"], string $glue = ',', bool $filter = true)
    {
        $result = [];
        if (!empty($str)) {
            $result = explode($glue, str_replace($search, $glue, $str));// 分隔符转换 转数组
            //过滤空值
            if ($filter) {
                $result = array_filter($result);
            }
        }

        return $result;
    }
}

/**
 * 是命令行模式
 * User: Sweeper
 * Time: 2023/9/10 10:41
 * @return bool
 */
function in_cli(): bool
{
    return PHP_SAPI === 'cli';
}

/**
 * 是 Windows
 * User: Sweeper
 * Time: 2023/9/10 10:41
 * @return bool
 */
function in_windows(): bool
{
    return stripos(PHP_OS_FAMILY, 'WIN') === 0;
}

/**
 * 服务器最大上传文件大小
 * 通过对比文件上传限制与post大小获取
 * @param bool $humanReadable 是否以可读方式返回
 * @return int
 */
function get_upload_max_size(bool $humanReadable = false): int
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
 * get phpinfo() as array
 * @return array
 */
function get_php_info(): array
{
    static $phpinfo;
    if ($phpinfo) {
        return $phpinfo;
    }

    $entitiesToUtf8 = function($input) {
        return preg_replace_callback('/(&#[0-9]+;)/', function($m) {
            return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
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

/**
 * get php core summary released info
 * @return string
 */
function get_php_release_summary(): string
{
    $info     = get_php_info();
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
function get_max_socket_timeout(int $ttf = 0): int
{
    $max_execute_timeout = ini_get('max_execution_time') ?: 0;
    $max_socket_timeout  = ini_get('default_socket_timeout') ?: 0;
    $max                 = (!$max_execute_timeout || !$max_socket_timeout) ? max($max_execute_timeout, $max_socket_timeout) : min($max_execute_timeout, $max_socket_timeout);
    if ($ttf && $max) {
        return max($max - $ttf, 1); //最低保持1s，避免0值
    }

    return $max;
}

if (!function_exists('get_file_permission')) {
    function get_file_permission($filename): string
    {
        clearstatcache(true, $filename);
        $perms = fileperms($filename);
        if (($perms&0xC000) === 0xC000) {
            $info = 's';
        } elseif (($perms&0xA000) === 0xA000) {
            $info = 'l';
        } elseif (($perms&0x8000) === 0x8000) {
            $info = '-';
        } elseif (($perms&0x6000) === 0x6000) {
            $info = 'b';
        } elseif (($perms&0x4000) === 0x4000) {
            $info = 'd';
        } elseif (($perms&0x2000) === 0x2000) {
            $info = 'c';
        } elseif (($perms&0x1000) === 0x1000) {
            $info = 'p';
        } else {
            $info = 'u';
        }

        $info .= (($perms&0x0100) ? 'r' : '-');
        $info .= (($perms&0x0080) ? 'w' : '-');
        $info .= (($perms&0x0040) ? (($perms&0x0800) ? 's' : 'x') : (($perms&0x0800) ? 'S' : '-'));
        $info .= (($perms&0x0020) ? 'r' : '-');
        $info .= (($perms&0x0010) ? 'w' : '-');
        $info .= (($perms&0x0008) ? (($perms&0x0400) ? 's' : 'x') : (($perms&0x0400) ? 'S' : '-'));
        $info .= (($perms&0x0004) ? 'r' : '-');
        $info .= (($perms&0x0002) ? 'w' : '-');
        $info .= (($perms&0x0001) ? (($perms&0x0200) ? 't' : 'x') : (($perms&0x0200) ? 'T' : '-'));

        return $info;
    }
}