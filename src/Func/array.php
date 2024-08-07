<?php
/**
 * 数组相关操作函数
 */

namespace Sweeper\HelperPhp\Func;

use Exception;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * 數組位置操作
 */
const ARRAY_POSING_HEAD   = 0x001;
const ARRAY_POSING_BEFORE = 0x002;
const ARRAY_POSING_AFTER  = 0x003;
const ARRAY_POSING_LAST   = 0x004;

/**
 * Array group by function
 * group array(); by by_key
 * @param array   $array
 * @param string  $by_key
 * @param boolean $force_unique
 * @return array $array handle result
 */
function array_group(array $array, string $by_key, bool $force_unique = false): array
{
    if (empty ($array) || !is_array($array)) {
        return $array;
    }
    if ($force_unique) {
        return array_column($array, null, $by_key);
    }
    $_result = [];
    foreach ($array as $item) {
        $_result[$item[$by_key]][] = $item;
    }

    return $_result;
}

/**
 * 划分范围，
 * 将指定开始···结束数值按照一定size进行分组。
 * 如对：2 ~ 9 数字进行分组，每组最大个数为3，则结果为： [2,3,4],[5,6,7],[8,9]
 * <pre>
 * 用法：
 * foreach(range_slice(2,9) as list($s, $e)){
 *      echo "$s ~ $e", PHP_EOL;
 * }
 * </pre>
 * @param int $start 开始下标
 * @param int $end   结束下标
 * @param int $size  每页大小
 * @return \Generator
 */
function range_slice(int $start, int $end, int $size): \Generator
{
    $page_count = ceil(($end - $start) / $size);
    for ($i = 0; $i < $page_count; $i++) {
        yield [$start + $i * $size, min($start + ($i + 1) * $size, $end)];
    }
}

/**
 * shuffle objects, key original assoc key
 * @param array|object $objects
 * @return array
 */
function object_shuffle($objects): array
{
    $keys = array_keys($objects);
    shuffle($keys);
    $tmp = [];
    foreach ($keys as $k) {
        $tmp[$k] = $objects[$k];
    }

    return $tmp;
}

/**
 * 随机返回数组元素，方便对array_rand调用
 * @param      $arr
 * @param int  $num         获取数量
 * @param null $key_or_keys 结果元素key，如果数量>1，该项为元素key数组
 * @return null|array|mixed 如果数量1，则返回数组元素值，如果数量>1，则返回结果关联数组
 */
function array_random(array $arr = [], int $num = 1, &$key_or_keys = null)
{
    if (!$arr) {
        return null;
    }
    if ($num === 1) {
        $key_or_keys = array_rand($arr, 1);

        return $arr[$key_or_keys];
    }
    if (count($arr) <= $num) {
        $key_or_keys = array_keys($arr);

        return $arr;
    }
    $key_or_keys = array_rand($arr, $num);
    $ret         = [];
    foreach ($key_or_keys as $k) {
        $ret[$k] = $arr[$k];
    }

    return $ret;
}

/**
 * @param array $keys
 * @param array $arr
 * @return bool
 */
function array_keys_exists(array $keys, array $arr): bool
{
    return !array_diff_key(array_flip($keys), $arr);
}

/**
 * 将多重数组值取出来
 * @param        $arr
 * @param string $original_key
 * @param string $original_key_name
 * @return array
 */
function plain_items($arr, string $original_key = '', string $original_key_name = 'original_key'): array
{
    if (count($arr) === count($arr, COUNT_RECURSIVE)) {
        $arr[$original_key_name] = $original_key;

        return [$arr];
    }

    $ret         = [];
    $plain_items = [];
    foreach ($arr as $k => $item) {
        $plain_items[] = plain_items($item, $k, $original_key_name);// $ret = array_merge($ret, plain_items($item, $k, $original_key_name));
    }

    return array_merge($ret, ...$plain_items);
}

/**
 * 重新组织PHP $_FILES数组格式
 * 以正确name维度返回数组
 * @param array $input
 * @return array
 */
function restructure_files(array $input): array
{
    $output = [];
    foreach ($input as $name => $array) {
        foreach ($array as $field => $value) {
            $pointer = &$output[$name];
            if (!is_array($value)) {
                $pointer[$field] = $value;
                continue;
            }
            $stack    = [&$pointer];
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($value), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $key => $v) {
                array_splice($stack, $iterator->getDepth() + 1);
                $pointer = &$stack[count($stack) - 1];
                $pointer = &$pointer[$key];
                $stack[] = &$pointer;
                if (!$iterator->callHasChildren()) {
                    $pointer[$field] = $v;
                }
            }
        }
    }

    return $output;
}

/**
 * array copy by fields
 * @param array $array
 * @param array $fields
 * @return array
 */
function array_copy_by_fields(array $array, array $fields): array
{
    $tmp = [];
    foreach ($fields as $field) {
        $tmp[$field] = $array[$field];
    }

    return $tmp;
}

/**
 * 检测KEY合并数组，增强array_merge
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_merge_recursive_distinct(array &$array1, array &$array2): array
{
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
            $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged [$key] = $value;
        }
    }

    return $merged;
}

/**
 * 清理数组中null的元素
 * @param array $data
 * @param bool  $recursive
 * @return array
 */
function array_clear_null(array $data, bool $recursive = true): array
{
    if (empty($data) || !is_array($data)) {
        return $data;
    }
    foreach ($data as $k => $item) {
        if ($item === null) {
            unset($data[$k]);
        }
        if ($recursive && is_array($item)) {
            $data[$k] = array_clear_null($item);
        }
    }

    return $data;
}

/**
 * 清理数组中empty的元素
 * @param      $data
 * @param bool $recursive
 * @return array
 */
function array_clear_empty($data, bool $recursive = true): array
{
    if (empty($data) || !is_array($data)) {
        return $data;
    }
    foreach ($data as $k => $item) {
        if (empty($item)) {
            unset($data[$k]);
        }
        if ($recursive && is_array($item)) {
            $data[$k] = array_clear_empty($item);
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }
    }

    return $data;
}

/**
 * 数组元素切换（支持关联数组）
 * @param array      $arr            数组
 * @param string|int $item_index_key 需要切换元素的key值（可以是关联数组的key）
 * @param number     $dir            移动方向
 * @return array
 * @throws \Exception
 */
function array_move_item(array $arr, $item_index_key, $dir): array
{
    if ($dir === ARRAY_POSING_HEAD) {
        $tmp = $arr[$item_index_key];
        unset($arr[$item_index_key]);
        array_unshift_assoc($arr, $item_index_key, $tmp);

        return $arr;
    }

    if ($dir === ARRAY_POSING_LAST) {
        $tmp = $arr[$item_index_key];
        unset($arr[$item_index_key]);
        $arr[$item_index_key] = $tmp;

        return $arr;
    }

    if ($dir === ARRAY_POSING_BEFORE) {
        $keys   = array_keys($arr);
        $values = array_values($arr);
        $nidx   = array_index($keys, $item_index_key);
        if ($nidx == 0) {
            return $arr;
        }
        $before                   = array_combine(array_slice($keys, 0, $nidx - 1), array_slice($values, 0, $nidx - 1));
        $before[$item_index_key]  = $arr[$item_index_key]; //當前
        $before[$keys[$nidx - 1]] = $values[$nidx - 1];    //上一個
        $after                    = array_combine(array_slice($keys, $nidx + 1), array_slice($values, $nidx + 1));

        return array_merge($before, $after);
    }

    if ($dir === ARRAY_POSING_AFTER) {
        $keys   = array_keys($arr);
        $values = array_values($arr);
        $nidx   = array_index($keys, $item_index_key);
        if ($nidx == count($arr) - 1) {
            return $arr;
        }
        $before                   = array_combine(array_slice($keys, 0, $nidx), array_slice($values, 0, $nidx));
        $before[$keys[$nidx + 1]] = $values[$nidx + 1];    //下一個
        $before[$item_index_key]  = $arr[$item_index_key]; //當前
        $after                    = array_combine(array_slice($keys, $nidx + 2), array_slice($values, $nidx + 2));

        return array_merge($before, $after);
    }

    throw new \LogicException('Array move direction no support:' . $dir);
}

/**
 * 清理数组字段
 * @param array $keep_fields
 * keep_fields 格式：
 * array(
 * 'id',
 * 'title',
 * 'url',
 * 'tags',
 * 'categories' => function($data){
 * if(!empty($data)){
 * foreach($data as $k=>$cat){
 * $data[$k] = array_clear_fields(array('id', 'name', 'url'), $cat);
 * }
 * }
 * return $data;
 * },
 * 'album' => array(
 * 'id',
 * 'cover_image_id',
 * 'cover_image'=>array(
 * 'id',
 * 'title',
 * 'url',
 * 'thumb_url'
 * ),
 * 'url'
 * ),
 * 'liked',
 * 'like_url',
 * 'fav_url',
 * 'thumb_url',
 * 'like_data_url',
 * 'link',
 * 'counter' => array(
 * 'visit_count',
 * 'like_count',
 * 'share_count',
 * 'collect_count'
 * ),
 * )
 * @param array $data
 * @return array
 */
function array_clear_fields(array $keep_fields, array $data): array
{
    foreach ($data as $k => $item) {
        $keep = false;
        foreach ($keep_fields as $fk => $cfg) {
            if (is_numeric($fk) && is_string($cfg) && $cfg == $k) {
                $keep = true;
                break;
            }

            if (is_string($fk) && $fk == $k && $data[$fk]) {
                if (is_callable($cfg)) {
                    $data[$k] = $cfg($item);
                    $keep     = true;
                    break;
                }

                if (is_array($cfg) && $item) {
                    $data[$k] = array_clear_fields($cfg, $item);
                    $keep     = true;
                    break;
                }
            }
        }

        if (!$keep) {
            unset($data[$k]);
        }
    }

    return $data;
}

/**
 * 根据数组项值，删除数组
 * @param array $arr
 * @param mixed $del_val
 */
function array_unset_by_value(array &$arr, $del_val)
{
    if (($key = array_search($del_val, $arr, false)) !== false) {
        unset($arr[$key]);
    }
}

/**
 * 对数组进行去空白
 * @param array $data      数据
 * @param array $fields    指定字段，为空表示所有字段
 * @param bool  $recursive 是否递归处理，如果递归，则data允许为任意维数组
 * @return array
 */
function array_trim_fields(array $data, array $fields = [], bool $recursive = true): array
{
    if (!$data || !is_array($data)) {
        return $data;
    }

    $copy = [];
    foreach ($data as $k => $item) {
        if ($recursive && is_array($item)) {
            $item = array_trim_fields($item, $fields, $recursive);
        } elseif (is_string($item) && (in_array($k, $fields, true) || !$fields)) {
            $item = trim($item);
        }
        $copy[$k] = $item;
    }

    return $copy;
}

/**
 * 获取数组最后一个数据
 * @param array $data
 * @param null  $key
 * @return null
 */
function array_last(array $data = [], &$key = null)
{
    if (!empty($data)) {
        $keys = array_keys($data);
        $key  = array_pop($keys);

        return $data[$key];
    }

    return null;
}

/**
 * 在数组开始位置压入关联数据
 * @param &$arr
 * @param $key
 * @param $val
 * @return int
 */
function array_unshift_assoc(&$arr, $key, $val): int
{
    $arr       = array_reverse($arr, true);
    $arr[$key] = $val;
    $arr       = array_reverse($arr, true);

    return count($arr);
}

/**
 * array sort by specified key
 * @param mixed
 * @return mixed
 * @example: array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
 */
function array_order_by($src_arr)
{
    if (empty($src_arr)) {
        return $src_arr;
    }
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = [];
            foreach ($data as $key => $row) {
                $tmp[$key] = $row[$field];
            }
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    array_multisort(...$args);

    return array_pop($args);
}

/**
 * 数组按照指定key排序
 * @param      $src_arr
 * @param      $keys
 * @param bool $miss_match_in_head 未命中值是否排列在头部
 * @return array
 */
function array_order_by_keys($src_arr, $keys, bool $miss_match_in_head = false): array
{
    if (empty($src_arr)) {
        return $src_arr;
    }
    $tmp = [];
    foreach ($keys as $k) {
        if (isset($src_arr[$k])) {
            $tmp[$k] = $src_arr[$k];
            unset($src_arr[$k]);
        }
    }
    if ($src_arr) {
        $tmp = $miss_match_in_head ? array_merge($src_arr, $tmp) : array_merge_after($tmp, $src_arr);
    }

    return $tmp;
}

/**
 * 获取数组元素key
 * @param $array
 * @param $compare_fn_or_value
 * @return bool|int|string
 */
function array_index($array, $compare_fn_or_value)
{
    foreach ($array as $k => $v) {
        if (is_callable($compare_fn_or_value)) {
            if ($compare_fn_or_value($v) === true) {
                return $k;
            }
        } else {
            if ($compare_fn_or_value == $v) {
                return $k;
            }
        }
    }

    return false;
}

/**
 * array sum by key
 * @param        $arr
 * @param string $key
 * @return float|int
 */
function array_sum_by($arr, string $key = '')
{
    if (!$key) {
        return array_sum($arr);
    }

    return array_sum(array_column($arr, $key));
}

/**
 * Set default values to array
 * Usage:
 * <pre>
 * $_GET = array_default($_GET, array('page_size'=>10), true);
 * </pre>
 * @param array $arr
 * @param array $values
 * @param bool  $reset_empty reset empty value in array
 * @return array
 */
function array_default(array $arr, array $values, bool $reset_empty = false): array
{
    foreach ($values as $k => $v) {
        if (!isset($arr[$k]) || ($reset_empty && empty($arr[$k]))) {
            $arr[$k] = $v;
        }
    }

    return $arr;
}

/**
 * check null in array
 * matched exp: [], [null], ['',null]
 * mismatched exp: ['']
 * @param array $arr
 * @return bool
 */
function null_in_array(array $arr): bool
{
    if (!$arr) {
        return true;
    }
    if (in_array(null, $arr, true)) {
        return true;
    }

    return false;
}

/**
 * filter array by specified keys
 * @param $arr
 * @param $keys
 * @return array
 * @example    array_filter_by_keys($data, array('key1','key2'));
 * array_filter_by_keys($data, 'key1', 'key2');
 * @deprecated 请使用 array_clear_fields
 */
function array_filter_by_keys($arr, $keys): array
{
    $args = is_array($keys) ? $keys : array_slice(func_get_args(), 1);
    $data = [];
    foreach ($args as $k) {
        $data[$k] = $arr[$k];
    }

    return $data;
}

/**
 * 创建Excel等电子表格里面的表头序列列表
 * @param int $column_size
 * @return array
 */
function array_make_spreadsheet_columns(int $column_size): array
{
    $ret = [];
    for ($column = 1; $column <= $column_size; $column++) {
        $ret[] = get_spreadsheet_column($column);
    }

    return $ret;
}

/**
 * 根据xpath，将数据压入数组
 * @param array  $data
 * @param string $path
 * @param        $value
 * @param string $glue
 */
function array_push_by_path(array &$data, string $path, $value, string $glue = '.')
{
    $path      = $path[0] == $glue ? $path : $glue . $path;
    $path      = preg_replace_callback('/' . preg_quote($glue) . '(\w+)?/', function($matches) {
        return "['$matches[1]']";
    }, $path);
    $val       = var_export($value, true);
    $statement = "\$data{$path} = $val;";
    eval($statement);
}

/**
 * 断言数组是否用拥有指定键名
 * @param $arr
 * @param $keys
 * @throws \Exception
 */
function assert_array_has_keys($arr, $keys)
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $arr)) {
            throw new Exception('Array key no exists:' . $key);
        }
    }
}

/**
 * 过滤子节点，以目录树方式返回
 * @param       $parent_id
 * @param       $all
 * @param array $opt
 * @param int   $level
 * @param array $group_by_parents
 * @return array
 */
function array_filter_subtree($parent_id, $all, array $opt = [], int $level = 0, array $group_by_parents = []): array
{
    $opt = array_merge([
        'return_as_tree' => false,             //以目录树返回，还是以平铺数组形式返回
        'level_key'      => 'tree_level',      //返回数据中是否追加等级信息,如果选项为空, 则不追加等级信息
        'id_key'         => 'id',              //主键键名
        'parent_id_key'  => 'parent_id',       //父级键名
        'children_key'   => 'children',         //返回子集key(如果是平铺方式返回,该选项无效
    ], $opt);

    $pn_k    = $opt['parent_id_key'];
    $lv_k    = $opt['level_key'];
    $id_k    = $opt['id_key'];
    $as_tree = $opt['return_as_tree'];
    $c_k     = $opt['children_key'];

    $result           = [];
    $group_by_parents = $group_by_parents ?: array_group($all, $pn_k);

    foreach ($all as $k => $item) {
        if ($item[$pn_k] == $parent_id) {
            $item[$lv_k] = $level;  //set level
            if (!$opt['return_as_tree']) {
                $result[] = $item;
            }
            if (isset($item[$id_k], $group_by_parents[$item[$id_k]]) && $group_by_parents[$item[$id_k]]) {
                $sub = array_filter_subtree($item[$id_k], $all, $opt, $level + 1, $group_by_parents);
                if (!empty($sub)) {
                    if ($as_tree) {
                        $item[$c_k] = $sub;
                    } else {
                        $result = array_merge($result, $sub);
                    }
                }
            }
            if ($as_tree) {
                $result[] = $item;
            }
        }
    }

    return $result;
}

/**
 * 插入指定数组在指定位置
 * @param array  $src_array
 * @param        $data
 * @param string $rel_key
 * @return array|int
 */
function array_insert_after(array $src_array, $data, string $rel_key = '')
{
    if (!array_key_exists($rel_key, $src_array)) {
        return array_push($src_array, $data);
    }

    $tmp_array = [];
    $len       = 0;

    foreach ($src_array as $key => $src) {
        $tmp_array[$key] = $src;
        $len++;
        if ($rel_key === $key) {
            break;
        }
    }
    $tmp_array[] = $data;

    return array_merge($tmp_array, array_slice($src_array, $len));
}

/**
 * 合并数组到指定位置之后
 * @param array  $src_array
 * @param array  $new_array
 * @param string $rel_key
 * @return array
 */
function array_merge_after(array $src_array = [], array $new_array = [], string $rel_key = ''): array
{
    if (!array_key_exists($rel_key, $src_array)) {
        return array_merge($src_array, $new_array);
    }

    $tmp_array = [];
    $len       = 0;

    foreach ($src_array as $key => $src) {
        $tmp_array[$key] = $src;
        $len++;
        if ($rel_key === $key) {
            break;
        }
    }
    $tmp_array = array_merge($tmp_array, $new_array);

    return array_merge($tmp_array, array_slice($src_array, $len));
}

/**
 * 检测数组是否为关联数组
 * @param array $array
 * @return boolean
 */
function is_assoc_array(array $array): bool
{
    return is_array($array) && array_values($array) !== $array;
}

/**
 * 函数  支持嵌套转换
 * @param array $data
 * @param array $rules    = array(
 *                        'dddd' => array('aaaa', 'bbbb')
 *                        ）,
 *                        转换为 : array['aaaaa']['bbb'] =  xxxx
 * @return mixed
 */
function array_transform(array $data, array $rules)
{
    $ret_array = [];
    foreach ($rules as $key => $value) {
        if (!is_int($key) && isset($data[$key])) {
            if (is_array($value) && !empty($value)) {
                $tmp = &$ret_array;
                foreach ($value as $v) {
                    $tmp = &$tmp[$v];
                }
                $tmp = $data[$key];
            } elseif (is_string($value)) {
                $ret_array[$value] = $data[$key];
            }
        } else {
            if (is_int($key) && isset($data[$value])) {
                $ret_array[$value] = $data[$value];
            }
        }
    }

    return $ret_array;
}

/**
 * 根据指定下标获取多维数组所有值，无下标时获取所有
 * @param string $key
 * @param array  $arr
 * @return array
 */
function array_value_recursive(array $arr, string $key = ''): array
{
    $val = [];
    array_walk_recursive($arr, function($v, $k) use ($key, &$val) {
        if (!$key || $k == $key) {
            $val[] = $v;
        }
    });

    return $val;
}

/**
 * get first item of array
 * @param array $data
 * @param null &$key
 * @return mixed|null
 */
function array_first(array $data = [], &$key = null)
{
    foreach ($data as $key => $item) {
        return $item;
    }

    return null;
}



