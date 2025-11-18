<?php
/**
 * Created by Administrator PhpStorm.
 * Author: Somnus <wili.lixiang@gmail.com>
 * Time: 2025/10/20 11:43:27
 * File: CurrencyConverter.php
 */

namespace Sweeper\HelperPhp\Tool;

use NumberFormatter;

class CurrencyConverter
{

    /**
     * @var array[]
     * @doc https://www.huilvbao.com/currency.html
     */
    private static $symbolMap = [
        '￥'   => ['code' => 'CNY', 'name' => '人民币'],
        '$'   => ['code' => 'USD', 'name' => '美元'],
        '€'   => ['code' => 'EUR', 'name' => '欧元'],
        '£'   => ['code' => 'GBP', 'name' => '英镑'],
        'HK$' => ['code' => 'HKD', 'name' => '港币'],
        'A$'  => ['code' => 'AUD', 'name' => '澳元'],
    ];

    public static function getCurrencyInfo($symbol): ?array
    {
        return static::$symbolMap[$symbol] ?? null;
    }

    public static function getCurrencyName($symbol): string
    {
        $info = static::getCurrencyInfo($symbol);

        return $info ? $info['name'] : '未知货币';
    }

    public static function getCurrencyCode($symbol): string
    {
        $info = static::getCurrencyInfo($symbol);

        return $info ? $info['code'] : '';
    }

    public static function getCurrencyNameBySymbol($symbol, $locale = 'zh_CN')
    {
        $info = static::getCurrencyInfo($symbol);

        if (!$info['code']) {
            return '未知货币';
        }

        $currencyCode = $info['code'];

        // 使用Intl扩展获取货币名称
        if (class_exists('NumberFormatter')) {
            return (new NumberFormatter($locale, NumberFormatter::CURRENCY))->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        }

        return $currencyCode;
    }

}
