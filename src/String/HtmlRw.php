<?php

namespace Sweeper\HelperPhp\String;

use function Sweeper\HelperPhp\Func\h;

/**
 * Trait HtmlRw
 * Html自动读写渲染切换
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 14:22
 * @Package \Sweeper\HelperPhp\String\HtmlRw
 */
trait HtmlRw
{

    use Html;

    protected static $disabled = true;

    public static function disabledAllElement(): void
    {
        static::$disabled = true;
    }

    public static function enabledAllElement(): void
    {
        static::$disabled = false;
    }

    public static function htmlRadioGroup(string $name, array $options, string $currentValue = '', string $wrapperTag = '', array $radioExtraAttributes = [])
    {
        if (!static::$disabled) {
            return Html::htmlRadioGroup($name, $options, $currentValue, $wrapperTag, $radioExtraAttributes);
        }

        return $currentValue !== '' ? $options[$currentValue] : '';
    }

    public static function htmlRadio(string $name, $value, string $title = '', bool $checked = false, array $attributes = []): string
    {
        if (!static::$disabled) {
            return Html::htmlRadio($name, $value, $title, $checked, $attributes);
        }

        return $checked ? $title : '';
    }

    public static function htmlCheckboxGroup(string $name, array $options, $currentValue = null, string $wrapperTag = '', array $checkboxExtraAttributes = []): ?string
    {
        if (!static::$disabled) {
            return Html::htmlCheckboxGroup($name, $options, $currentValue, $wrapperTag, $checkboxExtraAttributes);
        }

        return '';
    }

    public static function htmlCheckbox(string $name, $value, string $title = '', bool $checked = false, array $attributes = []): string
    {
        if (!static::$disabled) {
            return Html::htmlCheckbox($name, $value, $title, $checked, $attributes);
        }

        return $checked ? $title : '';
    }

    public static function htmlSelect(string $name, array $options, $currentValue = null, string $placeholder = '', array $attributes = [])
    {
        if (!static::$disabled) {
            return Html::htmlSelect($name, $options, $currentValue, $placeholder, $attributes);
        }

        //单层option
        if (count($options, COUNT_RECURSIVE) === count($options)) {
            return $options[$currentValue] ?: '';
        }

        //optgroup支持
        foreach ($options as $var1 => $var2) {
            if (is_array($var2)) {
                if ($var2[$currentValue]) {
                    return $var2[$currentValue];
                }
            } elseif ($var1 === $currentValue) {
                return $var2;
            }
        }

        return '';
    }

    /**
     * 构建HTML节点
     * @param string $tag
     * @param array  $attributes
     * @param string $innerHtml
     * @return string
     */
    public static function htmlElement(string $tag, array $attributes = [], string $innerHtml = ''): string
    {
        if (!static::$disabled) {
            return Html::htmlElement($tag, $attributes, $innerHtml);
        }
        $tag = strtolower($tag);
        if ($tag === 'input') {
            switch ($attributes['type']) {
                case 'text':
                case 'textarea':
                case 'number':
                case 'date':
                case 'datetime':
                    return $attributes['value'] !== '' ? h($attributes['value']) : '';
                default:
                    break;
            }
        }

        return '';
    }

}