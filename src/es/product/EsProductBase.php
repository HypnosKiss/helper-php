<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/20 13:46
 */

namespace Sweeper\HelperPhp\es\product;

use Sweeper\HelperPhp\es\Elasticsearch;

/**
 * 采购产品基类
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/20 21:01
 * @Path \es\product\EsProductBase
 */
class EsProductBase
{

    /**
     * 根据 SKU 获取产品信息
     * User: Sweeper
     * Time: 2023/3/17 19:30
     * @param string $sku                   sku/spu
     * @param string $_source               查询字段
     * @param array  $params                指定查询参数
     * @param mixed  ...$fieldHandleFuncArr 字段处理函数/闭包
     * @return array|mixed
     */
    public static function getProductInfoBySku(string $sku, string $_source = '', array $params = [], ...$fieldHandleFuncArr)
    {
        $result = [];
        if (!empty($sku)) {
            $params = array_replace_recursive([
                'index'   => static::INDEX,
                'id'      => $sku,
                '_source' => $_source,
            ], $params);
            try {
                $item = Elasticsearch::instance()->get($params);
                if (!empty($item)) {
                    if ($fieldHandleFuncArr) {
                        foreach ($fieldHandleFuncArr as $fieldHandleFunc) {
                            if ($fieldHandleFunc && is_callable($fieldHandleFunc)) {
                                $item = $fieldHandleFunc($item);
                            } else {
                                throw new \LogicException('SKU[' . $sku . ']参数错误，请提供可调用的函数;');
                            }
                        }
                    }
                    $source = $item['_source'] ?? $item;
                    $result = $_source && isset($source[$_source]) ? $source[$_source] : $source;
                }
            } catch (\Throwable $ex) {
                if ($ex->getCode() !== 404) {// 数据没找到时，返回“404”，不报异常
                    throw new \RuntimeException("{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})");
                }
            }
        }

        return $result;
    }

    /**
     * 根据 SPU 获取产品信息
     * User: Sweeper
     * Time: 2023/3/20 11:40
     * @param string $spu                   sku/spu
     * @param string $_source               查询字段
     * @param array  $params                指定查询参数
     * @param mixed  ...$fieldHandleFuncArr 字段处理函数/闭包
     * @return array|mixed
     */
    public static function getProductInfoBySpu(string $spu, string $_source = '', array $params = [], ...$fieldHandleFuncArr)
    {
        return static::getProductInfoBySku($spu, $_source, $params, ...$fieldHandleFuncArr);
    }

    /**
     * 根据 SKU 获取产品信息
     * User: Sweeper
     * Time: 2023/3/17 19:30
     * @param array  $skuList               sku/spu 列表
     * @param string $_source               查询字段
     * @param array  $params                指定查询参数
     * @param mixed  ...$fieldHandleFuncArr 字段处理函数/闭包
     * @return array
     */
    public static function getProductInfoBySkuList(array $skuList, string $_source = '', array $params = [], ...$fieldHandleFuncArr): array
    {
        $result = [];
        if (!empty($skuList)) {
            try {
                $params = array_replace_recursive([
                    'index'   => static::INDEX,
                    '_source' => $_source,
                    'body'    => [
                        'query' => [
                            'ids' => [
                                'values' => array_values($skuList),
                            ],
                        ],
                    ],
                    'size'    => count($skuList),
                ],
                    $params);
                $res    = Elasticsearch::instance()->search($params);
                $list   = $res['hits']['hits'];
                if (!empty($list)) {
                    foreach ($list as $item) {
                        $id = $item['_id'];
                        if ($fieldHandleFuncArr) {
                            foreach ($fieldHandleFuncArr as $fieldHandleFunc) {
                                if ($fieldHandleFunc && is_callable($fieldHandleFunc)) {
                                    $item = $fieldHandleFunc($item);
                                } else {
                                    throw new \LogicException('参数错误，请提供可调用的函数;');
                                }
                            }
                        }
                        $source      = $item['_source'] ?? $item;
                        $result[$id] = $_source && isset($source[$_source]) ? $source[$_source] : $source;
                    }
                }
            } catch (\Throwable $ex) {
                if ($ex->getCode() !== 404) {// 数据没找到时，返回“404”，不报异常
                    throw new \RuntimeException("{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})");
                }
            }
        }

        return $result;
    }

    /**
     * 据 SPU 获取产品信息
     * @param array  $spuList               SPU 列表
     * @param string $_source               查询字段
     * @param array  $params                指定查询参数
     * @param mixed  ...$fieldHandleFuncArr 字段处理函数/闭包
     * @return array
     */
    public static function getProductInfoBySpuList(array $spuList, string $_source = '', array $params = [], ...$fieldHandleFuncArr): array
    {
        return static::getProductInfoBySkuList($spuList, $_source, array_replace_recursive(['size' => 10000], $params), ...$fieldHandleFuncArr);
    }

    /**
     * 使用指定字段（闭包使用例子）
     * User: Sweeper
     * Time: 2023/3/17 19:30
     * @param string $field 指定返回字段
     * @return \Closure
     */
    public static function withFields(string $field = ''): \Closure
    {
        return static function (array $item) use ($field) {
            $item   = $item['_source'] ?? $item;
            $result = [];
            if (strlen(trim($field)) > 1) {
                $fields = explode(',', str_replace(['，', ' '], ',', $field));
                foreach ($fields as $_field) {
                    if (isset($item[$_field])) {
                        $result[$_field] = $item[$_field];
                    }
                }
            } else {
                $result = $item;
            }

            return $result;
        };
    }

}
