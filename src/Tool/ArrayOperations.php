<?php

namespace Sweeper\HelperPhp\Tool;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 22:58
 * @Path \Sweeper\HelperPhp\Tool\ArrayOperations
 */
class ArrayOperations
{

    /**
     * 下划线转驼峰
     * @param array $data
     * @return array
     */
    public static function convertLineToHump(array $data): array
    {
        $result = [];
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $result[static::camelize($key)] = static::convertLineToHump($item);
            } else {
                $result[static::camelize($key)] = $item;
            }
        }

        return $result;
    }

    /**
     * 驼峰转下划线
     * @param array $data
     * @return array
     */
    public static function convertHumpToLine(array $data): array
    {
        $result = [];
        foreach ($data as $key => $item) {
            if (is_array($item) || is_object($item)) {
                $result[static::uncamelize($key)] = static::convertHumpToLine((array)$item);
            } else {
                $result[static::uncamelize($key)] = $item;
            }
        }

        return $result;
    }

    /**
     * 下划线转驼峰
     * @param        $uncamelized_words
     * @param string $separator
     * @return string
     */
    private static function camelize($uncamelized_words, string $separator = '_'): string
    {
        $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));

        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
    }

    /**
     * 驼峰转下划线
     * @param        $camelCaps
     * @param string $separator
     * @return string
     */
    private static function uncamelize($camelCaps, string $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

}