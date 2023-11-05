<?php

namespace Sweeper\HelperPhp\String;

use InvalidArgumentException;

use function Sweeper\HelperPhp\Func\array_clear_null;
use function Sweeper\HelperPhp\Func\array_first;
use function Sweeper\HelperPhp\Func\substr_utf8;

/**
 * Trait Html
 * HTML字符串相关操作类封装
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 14:00
 * @Package \Sweeper\HelperPhp\String\Html
 */
trait Html
{

    /**
     * html单标签节点列表
     * @var array
     */
    protected static $SELF_CLOSING_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
        'command',
        'keygen',
        'menuitem',
    ];

    /**
     * 构建select节点，支持optgroup模式
     * @param string       $name
     * @param array        $options 选项数据，
     *                              如果是分组模式，格式为：[value=>text, label=>options, ...]
     *                              如果是普通模式，格式为：options: [value1=>text, value2=>text,...]
     * @param string|array $currentValue
     * @param string       $placeholder
     * @param array        $attributes
     * @return string
     */
    public static function htmlSelect(string $name, array $options, $currentValue = null, string $placeholder = '', array $attributes = []): string
    {
        $attributes = array_merge($attributes, [
            'name'        => $name ?: null,
            'placeholder' => $placeholder ?: null,
        ]);

        //多选
        if (is_array($currentValue)) {
            $attributes['multiple'] = 'multiple';
        }

        $option_html = $placeholder ? static::htmlOption($placeholder) : '';

        //单层option
        if (count($options, COUNT_RECURSIVE) === count($options)) {
            $option_html .= static::htmlOptions($options, $currentValue);
        } //optgroup支持
        else {
            foreach ($options as $var1 => $var2) {
                if (is_array($var2)) {
                    $option_html .= static::htmlOptionGroup($var1, $var2, $currentValue);
                } else {
                    $option_html .= static::htmlOption($var2, $var1, $currentValue);
                }
            }
        }

        return static::htmlElement('select', $attributes, $option_html);
    }

    /**
     * 构建select选项
     * @param array        $options      [value=>text,...] option data 选项数组
     * @param string|array $currentValue 当前值
     * @return string
     */
    public static function htmlOptions(array $options, $currentValue = null): string
    {
        $html = '';
        foreach ($options as $val => $ti) {
            $html .= static::htmlOption($ti, $val, static::htmlValueCompare($val, $currentValue));
        }

        return $html;
    }

    /**
     * 构建option节点
     * @param string $text 文本，空白将被转义成&nbsp;
     * @param string $value
     * @param bool   $selected
     * @param array  $attributes
     * @return string
     */
    public static function htmlOption(string $text, string $value = '', bool $selected = false, array $attributes = []): string
    {
        return static::htmlElement('option', array_merge([
            'selected' => $selected ? 'selected' : null,
            'value'    => $value,
        ], $attributes), static::htmlFromText($text));
    }

    /**
     * 构建optgroup节点
     * @param string       $label
     * @param array        $options
     * @param string|array $currentValue 当前值
     * @return string
     */
    public static function htmlOptionGroup(string $label, array $options, $currentValue = null): string
    {
        $option_html = static::htmlOptions($options, $currentValue);

        return static::htmlElement('optgroup', ['label' => $label], $option_html);
    }

    /**
     * 构建textarea
     * @param string $name
     * @param string $value
     * @param array  $attributes
     * @return string
     */
    public static function htmlTextArea(string $name, string $value = '', array $attributes = []): string
    {
        $attributes['name'] = $name;

        return static::htmlElement('textarea', $attributes, htmlspecialchars($value));
    }

    /**
     * 构建hidden表单节点
     * @param string $name
     * @param string $value
     * @return string
     */
    public static function htmlHidden(string $name, string $value = ''): string
    {
        return static::htmlElement('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }

    /**
     * 构建数据hidden列表
     * @param array $dataList 数据列表（可以多维数组）
     * @return string
     */
    public static function htmlHiddenList(array $dataList): string
    {
        $html    = '';
        $entries = explode('&', http_build_query($dataList));
        foreach ($entries as $entry) {
            $tmp   = explode('=', $entry);
            $key   = $tmp[0];
            $value = $tmp[1] ?? null;
            $html  .= static::htmlHidden(urldecode($key), urldecode($value)) . PHP_EOL;
        }

        return $html;
    }

    /**
     * 构建Html数字输入
     * @param string $name
     * @param string $value
     * @param array  $attributes
     * @return string
     */
    public static function htmlNumber(string $name, string $value = '', array $attributes = []): string
    {
        $attributes['type']  = 'number';
        $attributes['name']  = $name;
        $attributes['value'] = $value;

        return static::htmlElement('input', $attributes);
    }

    /**
     * @param string $name
     * @param array  $options              选项[value=>title,...]格式
     * @param string $currentValue
     * @param string $wrapperTag           每个选项外部包裹标签，例如li、div等
     * @param array  $radioExtraAttributes 每个radio额外定制属性
     * @return string
     */
    public static function htmlRadioGroup(string $name, array $options, string $currentValue = '', string $wrapperTag = '', array $radioExtraAttributes = []): ?string
    {
        $html = [];
        foreach ($options as $val => $ti) {
            $html[] = static::htmlRadio($name, $val, $ti, static::htmlValueCompare($val, $currentValue), $radioExtraAttributes);
        }

        if ($wrapperTag) {
            $rst = '';
            foreach ($html as $h) {
                $rst .= ' ' . static::htmlElement($wrapperTag, [], $h);
            }

            return $rst;
        }

        return implode(' ', $html);
    }

    /**
     * 构建 radio按钮
     * 使用 label>(input:radio+{text}) 结构
     * @param string $name
     * @param mixed  $value
     * @param string $title
     * @param bool   $checked
     * @param array  $attributes
     * @return string
     */
    public static function htmlRadio(string $name, $value, string $title = '', bool $checked = false, array $attributes = []): string
    {
        $attributes['type']  = 'radio';
        $attributes['name']  = $name;
        $attributes['value'] = $value;
        if ($checked) {
            $attributes['checked'] = 'checked';
        }

        return static::htmlElement('label', [], static::htmlElement('input', $attributes) . $title);
    }

    /**
     * @param string       $name
     * @param array        $options                 选项[value=>title,...]格式
     * @param string|array $currentValue
     * @param string       $wrapperTag              每个选项外部包裹标签，例如li、div等
     * @param array        $checkboxExtraAttributes 每个checkbox额外定制属性
     * @return string
     */
    public static function htmlCheckboxGroup(string $name, array $options, $currentValue = null, string $wrapperTag = '', array $checkboxExtraAttributes = []): ?string
    {
        $html = [];
        foreach ($options as $val => $ti) {
            $html[] = static::htmlCheckbox($name, $val, $ti, static::htmlValueCompare($val, $currentValue), $checkboxExtraAttributes);
        }
        if ($wrapperTag) {
            $rst = '';
            foreach ($html as $h) {
                $rst .= ' ' . static::htmlElement($wrapperTag, [], $h);
            }

            return $rst;
        }

        return implode(' ', $html);
    }

    /**
     * 构建 checkbox按钮
     * 使用 label>(input:checkbox+{text}) 结构
     * @param string $name
     * @param mixed  $value
     * @param string $title
     * @param bool   $checked
     * @param array  $attributes
     * @return string
     */
    public static function htmlCheckbox(string $name, $value, string $title = '', bool $checked = false, array $attributes = []): string
    {
        $attributes['type']  = 'checkbox';
        $attributes['name']  = $name;
        $attributes['value'] = $value;
        if ($checked) {
            $attributes['checked'] = 'checked';
        }
        $checkbox = static::htmlElement('input', $attributes);
        if (!$title) {
            return $checkbox;
        }

        return static::htmlElement('label', [], $checkbox . $title);
    }

    /**
     * 构建进度条（如果没有设置value，可充当loading效果使用）
     * @param null|number $value
     * @param null|number $max
     * @param array       $attributes
     * @return string
     */
    public static function htmlProgress($value = null, $max = null, array $attributes = []): string
    {
        //如果有max，必须大于0
        if (isset($max) && (float)$max <= 0) {
            throw new InvalidArgumentException('Progress max should bigger or equal to zero');
        }
        //有设置max，value范围必须在0~max
        if (isset($value, $max) && $value > $max) {
            throw new InvalidArgumentException('Progress value should less or equal than max');
        }
        $attributes['max']   = $max;
        $attributes['value'] = $value;

        return static::htmlElement('progress', $attributes);
    }

    /**
     * Html循环滚动进度条
     * alias to htmlProgress
     * @param array $attributes
     * @return string
     */
    public static function htmlLoadingBar(array $attributes = []): string
    {
        return static::htmlProgress(null, null, $attributes);
    }

    /**
     * Html范围选择器
     * @param string $name
     * @param string $value 当前值
     * @param int    $min   最小值
     * @param int    $max   最大值
     * @param int    $step  步长
     * @param array  $attributes
     * @return string
     */
    public static function htmlRange(string $name, string $value, int $min = 0, int $max = 100, int $step = 1, array $attributes = []): string
    {
        $attributes['type']  = 'range';
        $attributes['name']  = $name;
        $attributes['value'] = $value;
        $attributes['min']   = $min;
        $attributes['max']   = $max;
        $attributes['step']  = $step;

        return static::htmlElement('input', $attributes);
    }

    /**
     * 获取HTML摘要信息
     * @param string $htmlContent
     * @param int    $len
     * @return string
     */
    public static function htmlAbstract(string $htmlContent, int $len = 200): string
    {
        $str = str_replace(["\n", "\r"], "", $htmlContent);
        $str = preg_replace('/<br([^>]*)>/i', '$$NL', $str);
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES);
        $str = substr($str, 0, $len);
        $str = str_replace('$$NL', '<br/>', $str);

        //移除头尾空白行
        $str = preg_replace('/^(<br[^>]*>)*/i', '', $str);

        return preg_replace('/(<br[^>]*>)*$/i', '', $str);
    }

    /**
     * 构建Html input:text文本输入框
     * @param string $name
     * @param string $value
     * @param array  $attributes
     * @return string
     */
    public static function htmlText(string $name, string $value = '', array $attributes = []): string
    {
        $attributes['type']  = 'text';
        $attributes['name']  = $name;
        $attributes['value'] = $value;

        return static::htmlElement('input', $attributes);
    }

    /**
     * 构建Html日期输入框
     * @param string $name
     * @param string $dateOrTimestamp
     * @param array  $attributes
     * @return string
     */
    public static function htmlDate(string $name, string $dateOrTimestamp = '', array $attributes = []): string
    {
        $attributes['type'] = 'date';
        $attributes['name'] = $name;
        if ($dateOrTimestamp) {
            $attributes['value'] = is_numeric($dateOrTimestamp) ? date('Y-m-d', $dateOrTimestamp) :
                date('Y-m-d', strtotime($dateOrTimestamp));
        }

        return static::htmlElement('input', $attributes);
    }

    /**
     * 构建Html日期+时间输入框
     * @param string $name
     * @param string $datetimeOrTimestamp
     * @param array  $attributes
     * @return string
     */
    public static function htmlDateTime(string $name, string $datetimeOrTimestamp = '', array $attributes = []): string
    {
        $attributes['type'] = 'datetime-local';
        $attributes['name'] = $name;
        if ($datetimeOrTimestamp) {
            $attributes['value'] = is_numeric($datetimeOrTimestamp) ? date('Y-m-dTH:i',
                $datetimeOrTimestamp) : date('Y-m-d', strtotime($datetimeOrTimestamp));
        }

        return static::htmlElement('input', $attributes);
    }

    /**
     * 构建Html月份选择器
     * @param string   $name
     * @param int|null $currentMonth 当前月份，范围1~12表示
     * @param string   $format       月份格式，与date函数接受格式一致
     * @param array    $attributes   属性
     * @return string
     */
    public static function htmlMonthSelect(string $name, int $currentMonth = null, string $format = 'm', array $attributes = []): string
    {
        $opts   = [];
        $format = $format ?: 'm';
        for ($i = 1; $i <= 12; $i++) {
            $opts[$i] = date($format, strtotime('1970-' . $currentMonth . '-01'));
        }

        return static::htmlSelect($name, $opts, $currentMonth, $attributes['placeholder'], $attributes);
    }

    /**
     * 构建Html年份选择器
     * @param string   $name
     * @param int|null $currentYear 当前年份
     * @param int      $startYear   开始年份（缺省为1970）
     * @param int      $endYear     结束年份（缺省为今年）
     * @param array    $attributes
     * @return string
     */
    public static function htmlYearSelect(string $name, int $currentYear = null, int $startYear = 1970, int $endYear = 0, array $attributes = []): string
    {
        $startYear = $startYear ?: 1970;
        $endYear   = (int)($endYear ?: date('Y'));
        $opts      = [];
        for ($i = $startYear; $i <= $endYear; $i++) {
            $opts[$i] = $i;
        }

        return static::htmlSelect($name, $opts, $currentYear, $attributes['placeholder'], $attributes);
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
        $tag        = strtolower($tag);
        $single_tag = in_array($tag, static::$SELF_CLOSING_TAGS, true);
        $html       = "<$tag ";

        //针对textarea标签，识别value填充到inner_html中
        if ($tag === 'textarea' && isset($attributes['value'])) {
            $innerHtml = $innerHtml ?: $attributes['value'];
            unset($attributes['value']);
        }

        $html .= static::htmlAttributes($attributes);
        $html .= $single_tag ? "/>" : ">" . $innerHtml . "</$tag>";

        return $html;
    }

    /**
     * 构建HTML链接
     * @param string $innerHtml
     * @param string $href
     * @param array  $attributes
     * @return string
     */
    public static function htmlLink(string $innerHtml, string $href = '', array $attributes = []): string
    {
        $attributes['href'] = $href;

        return static::htmlElement('a', $attributes, $innerHtml);
    }

    /***
     * 构建css节点
     * @param string $href
     * @param array  $attributes
     * @return string
     */
    public static function htmlCss(string $href, array $attributes = []): string
    {
        return static::htmlElement('link', array_merge([
            'type'  => 'text/css',
            'rel'   => 'stylesheet',
            'media' => 'all',
            'href'  => $href,
        ], $attributes));
    }

    /***
     * 构建js节点
     * @param string $src
     * @param array  $attributes
     * @return string
     */
    public static function htmlJs(string $src, array $attributes = []): string
    {
        return static::htmlElement('script', array_merge([
            'type'    => 'text/javascript',
            'charset' => 'utf-8',
            'src'     => $src,
        ], $attributes));
    }

    /**
     * 构建Html日期输入
     * @param string $name
     * @param string $value
     * @param array  $attributes
     * @return string
     */
    public static function htmlDateInput(string $name, string $value = '', array $attributes = []): string
    {
        $attributes['type']  = 'date';
        $attributes['name']  = $name;
        $attributes['value'] = ($value && strpos($value, '0000') !== false) ? date('Y-m-d', strtotime($value)) : '';

        return static::htmlElement('input', $attributes);
    }

    /**
     * 构建Html时间输入
     * @param string $name
     * @param string $value
     * @param array  $attributes
     * @return string
     */
    public static function htmlDateTimeInput(string $name, string $value = '', array $attributes = []): string
    {
        $attributes['type']  = 'datetime-local';
        $attributes['name']  = $name;
        $attributes['value'] = ($value && strpos($value, '0000') === false) ? date("Y-m-d\\TH:i:s", strtotime($value)) : '';

        return static::htmlElement('input', $attributes);
    }

    /**
     * 构建DataList
     * @param string $id
     * @param array  $data [val=>title,...]
     * @return string
     */
    public static function htmlDataList(string $id, array $data = []): string
    {
        $opts = '';
        foreach ($data as $value => $label) {
            $opts .= '<option value="' . $value . '" label="' . $label . '">';
        }

        return static::htmlElement('datalist', ['id' => $id], $opts);
    }

    /**
     * submit input
     * @param mixed $value
     * @param array $attributes
     * @return string
     */
    public static function htmlInputSubmit($value, array $attributes = []): string
    {
        $attributes['type']  = 'submit';
        $attributes['value'] = $value;

        return static::htmlElement('input', $attributes);
    }

    /**
     * no script support html
     * @param $html
     * @return string
     */
    public static function htmlNoScript($html): string
    {
        return '<noscript>' . $html . '</noscript>';
    }

    /**
     * submit button
     * @param string $innerHtml
     * @param array  $attributes
     * @return string
     */
    public static function htmlButtonSubmit(string $innerHtml, array $attributes = []): string
    {
        $attributes['type'] = 'submit';

        return static::htmlElement('button', $attributes, $innerHtml);
    }

    /**
     * 构建table节点
     * @param             $data
     * @param array|false $headers 表头列表 [字段名 => 别名, ...]，如为false，表示不显示表头
     * @param string      $caption
     * @param array       $attributes
     * @return string
     */
    public static function htmlTable($data, $headers = [], string $caption = '', array $attributes = []): string
    {
        $html = $caption ? static::htmlElement('caption', [], $caption) : '';
        if (is_array($headers) && $data) {
            $all_fields = array_keys(array_first($data));
            $headers    = $headers ?: array_combine($all_fields, $all_fields);
            $html       .= '<thead><tr>';
            foreach ($headers as $alias) {
                $html .= "<th>$alias</th>";
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($data ?: [] as $row) {
            $html .= '<tr>';
            if ($headers) {
                foreach ($headers as $field => $alias) {
                    $html .= "<td>$row[$field]</td>";
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        return static::htmlElement('table', $attributes, $html);
    }

    /**
     * 构建HTML节点属性
     * 修正pattern，disabled在false情况下HTML表现
     * @param array $attributes
     * @return string
     */
    public static function htmlAttributes(array $attributes = []): string
    {
        $attributes = array_clear_null($attributes);
        $html       = [];
        foreach ($attributes as $k => $v) {
            if ($k === 'disabled' && $v === false) {
                continue;
            }
            $html[] = "$k=\"" . $v . "\"";
        }

        return implode(' ', $html);
    }

    /**
     * 转化明文文本到HTML
     * @param string $text
     * @param null   $len
     * @param string $tail
     * @param bool   $overLength
     * @return string|string[]
     */
    public static function htmlFromText(string $text, $len = null, string $tail = '...', bool &$overLength = false)
    {
        if ($len) {
            $text = substr_utf8($text, $len, $tail, $overLength);
        }
        $html = htmlspecialchars($text);

        return str_replace(["\r", ' ', "\n", "\t"], ['', '&nbsp;', '<br/>', '&nbsp;&nbsp;&nbsp;&nbsp;'], $html);
    }

    /**
     * HTML数值比较（通过转换成字符串之后进行严格比较）
     * @param string              $str1
     * @param string|number|array $data
     * @return bool 是否相等
     */
    public static function htmlValueCompare(string $str1, $data): bool
    {
        if (is_array($data)) {
            foreach ($data as $val) {
                if ((string)$val === $str1) {
                    return true;
                }
            }

            return false;
        }

        return $str1 === (string)$data;
    }

}
