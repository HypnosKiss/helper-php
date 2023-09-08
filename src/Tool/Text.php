<?php

namespace Sweeper\HelperPhp\Tool;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use AlibabaCloud\Client\Result\Result;

/**
 * Class Text
 * 通用版翻译以解决全场景语言障碍为目标，多领域适用，现可支持243个语言方向的翻译。 243个语言方向覆盖
 * 包括中文、英文、韩语、日文、法语、西班牙语、葡萄牙语、意大利语、俄语、阿拉伯语、土耳其语、印尼语、越南语、泰语、波兰语、德语，以上16个语言对可支持两两互译。
 * 另支持3个语言方向的翻译：
 * 源语言    目标语言
 * 中文    中文繁
 * 中文繁    中文
 * 中文    中文粤语
 * 语言代码说明
 * 语言           代码
 * 中文/中文简体    zh
 * 中文繁体    zh-tw
 * 中文粤语    yue
 * 英文    en
 * 日语    ja
 * 韩语    ko
 * 西班牙语    es
 * 法语    fr
 * 葡萄牙语    pt
 * 意大利语    it
 * 俄语    ru
 * 阿拉伯语    ar
 * 土耳其语    tr
 * 泰语    th
 * 印尼语    id
 * 越南语    vi
 * 马来语    ms
 * 希伯来语    he
 * 印地语    hi
 * 波兰语    pl
 * 荷兰语    nl
 * 德语    de
 * @package Sweeper\HelperPhp\Tool\Text
 * @doc https://help.aliyun.com/document_detail/2505918.html?spm=a2c4g.125181.0.0.309b70bbECujKM
 * @doc https://www.aliyun.com/product/ai/base_alimt
 */
class Text
{

    public $appKey   = '';

    public $appId    = '';

    public $regionId = 'cn-hangzhou';

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function setAppKey(string $appKey): self
    {
        $this->appKey = $appKey;

        return $this;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;

        return $this;
    }

    public function getRegionId(): string
    {
        return $this->regionId;
    }

    public function setRegionId(string $regionId): self
    {
        $this->regionId = $regionId;

        return $this;
    }

    //场景

    /** @var string 商品标题 */
    public const SCENE_TITLE = 'title';

    /** @var string 商品描述 */
    public const SCENE_DESCRIPTION = 'description';

    /** @var string 商品沟通 */
    public const SCENE_COMMUNICATION = 'communication';

    /** @var string 医疗 */
    public const SCENE_MEDICAL = 'medical';

    /** @var string 社交 */
    public const SCENE_SOCIAL = 'social';

    //翻译文本的格式

    public const FORMAT_TYPE_HTML = 'html';

    public const FORMAT_TYPE_TEXT = 'text';

    //翻译支持的语言

    /** @var string 自动识别原文语种 */
    public const AUTO = 'auto';

    /** @var string 中文 */
    public const ZH = 'zh';

    /** @var string 英文 */
    public const EN = 'en';

    /** @var string 法语 */
    public const FR = 'fr';

    /** @var string 中文繁体 */
    public const ZH_TW = 'zh-tw';

    /** @var string 德文 */
    public const DE = 'de';

    /** @var string 韩语 */
    public const KO = 'ko';

    /** @var string 日语 */
    public const JA = 'ja';

    /** @var string 泰语 */
    public const TH = 'th';

    /** @var string 马来语 */
    public const MS = 'ms';

    /** @var string 印尼语 */
    public const ID = 'id';

    /** @var string 印尼语 */
    public const VI = 'vi';

    /** @var string[] */
    public const TRANSLATION_LANGUAGE = [
        self::AUTO => '自动',
        self::EN   => '英文',
        self::FR   => '法语',
        self::DE   => '德语',
        self::KO   => '韩语',
        self::JA   => '日语',
        self::TH   => '泰语',
        self::MS   => '马来语',
        self::ID   => '印尼语',
        self::VI   => '越南语',
    ];

    /**
     * 设置默认客户端
     * User: Sweeper
     * Time: 2023/9/7 13:07
     * @param string $appId
     * @param string $appKey
     * @param string $regionId
     * @return $this
     */
    public function setDefaultClient(string $appId = '', string $appKey = '', string $regionId = ''): self
    {
        AlibabaCloud::accessKeyClient($appId ?: $this->getAppId(), $appKey ?: $this->getAppKey())->regionId($regionId ?: $this->getRegionId())->asDefaultClient();

        return $this;
    }

    /**
     * 通用版翻译
     * @param string $text   原文
     * @param string $source 源语言
     * @param string $target 目标语言
     * @param string $type   翻译文本的格式
     * @return array
     * @throws \Exception
     */
    public function translateGeneral(string $text, string $source = self::ZH, string $target = self::EN, string $type = self::FORMAT_TYPE_TEXT): ?array
    {
        try {
            $result = AlibabaCloud::alimt()
                                  ->v20181012()
                                  ->translateGeneral()
                                  ->method('POST')
                                  ->withSourceLanguage($source)
                                  ->withSourceText($text)
                                  ->withFormatType($type)
                                  ->withTargetLanguage($target)
                                  ->request();

            return static::toResult($result, $source, $target);
        } catch (ServerException|ClientException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 电商版翻译
     * @param string $text   原文
     * @param string $source 源语言
     * @param string $target 目标语言
     * @param string $type   翻译文本的格式
     * @param string $scene  设置场景，商品标题:title，商品描述:description，商品沟通:communication
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function translateECommerce(string $text, string $source = self::ZH, string $target = self::EN, string $type = self::FORMAT_TYPE_TEXT, string $scene = self::SCENE_TITLE): ?array
    {
        try {
            $result = AlibabaCloud::alimt()
                                  ->v20181012()
                                  ->translateECommerce()
                                  ->method('POST')
                                  ->withSourceLanguage($source)
                                  ->withScene($scene)
                                  ->withSourceText($text)
                                  ->withFormatType($type)
                                  ->withTargetLanguage($target)
                                  ->request();

            return static::toResult($result, $source, $target);
        } catch (ServerException|ClientException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 机器翻译专业版
     * @param string $sourceText                      必填 待翻译内容
     * @param string $sourceLanguage                  必填 原文语言
     * @param string $targetLanguage                  必填 译文语言
     * @param string $formatType                      必填 翻译文本的格式，html(网页格式。设置此参数将对待翻译文本以及翻译后文本按照html格式进行处理)、
     *                                                text(文本格式。设置此参数将对传入待翻译文本以及翻译后结果不做文本格式处理，统一按纯文本格式处理)。
     * @param string $scene                           必填 场景可选取值：商品标题（title），商品描述（description），商品沟通（communication），医疗（medical），社交（social)
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public static function translate(string $sourceText, string $sourceLanguage = self::ZH, string $targetLanguage = self::EN, string $formatType = self::FORMAT_TYPE_TEXT, string $scene = self::SCENE_TITLE): ?array
    {
        try {
            $result = AlibabaCloud::alimt()
                                  ->v20181012()
                                  ->translate()
                                  ->method('POST')
                                  ->withSourceLanguage($sourceLanguage)
                                  ->withScene($scene)
                                  ->withSourceText($sourceText)
                                  ->withFormatType($formatType)
                                  ->withTargetLanguage($targetLanguage)
                                  ->request();

            return static::toResult($result, $sourceLanguage, $targetLanguage);
        } catch (ServerException|ClientException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 机器批量翻译专业版
     * @param array  $sourceText                      必填 待翻译内容
     * @param string $sourceLanguage                  必填 原文语言
     * @param string $targetLanguage                  必填 译文语言
     * @param string $formatType                      必填 翻译文本的格式，html(网页格式。设置此参数将对待翻译文本以及翻译后文本按照html格式进行处理)、
     *                                                text(文本格式。设置此参数将对传入待翻译文本以及翻译后结果不做文本格式处理，统一按纯文本格式处理)。
     * @param string $scene                           必填 场景可选取值：商品标题（title），商品描述（description），商品沟通（communication），医疗（medical），社交（social)
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public static function batchTranslate(array $sourceText, string $sourceLanguage = self::ZH, string $targetLanguage = self::EN, string $formatType = self::FORMAT_TYPE_TEXT, string $scene = self::SCENE_TITLE): ?array
    {
        try {
            //提示10004 参数错误，检查 ApiType；提示10033 语种拼写错误，检查 SourceLanguage
            $query  = [
                //版本类型 通用版 translate_standard 专业版 translate_ecommerce
                'ApiType'        => "translate_ecommerce",
                'Scene'          => $scene,
                'TargetLanguage' => $targetLanguage,
                'SourceLanguage' => $sourceLanguage,
                'FormatType'     => $formatType,
                'SourceText'     => json_encode($sourceText),
            ];
            $result = AlibabaCloud::rpc()
                                  ->product('alimt')
                                  ->scheme('https') // https | http
                                  ->version('2018-10-12')
                                  ->action('GetBatchTranslate')
                                  ->method('POST')
                                  ->host('mt.aliyuncs.com')
                                  ->options([
                                      'query' => $query,
                                  ])
                                  ->request();

            return static::toResult($result, $sourceLanguage, $targetLanguage);
        } catch (ServerException|ClientException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 返回结果
     * @param \AlibabaCloud\Client\Result\Result $result
     * @param                                    $sourceLanguage
     * @param                                    $targetLanguage
     * @return array    [$isSuccess, $msg, $data]  code 0 为成功 1 为失败，成功 data 为翻译后的内容 失败 msg 为 翻译失败提示
     */
    public static function toResult(Result $result, $sourceLanguage, $targetLanguage): array
    {
        $ret       = $result->toArray();
        $isSuccess = $ret['Code'] === 200;
        $data      = $isSuccess ? $ret['Data']['Translated'] : '';
        if ($isSuccess && isset($ret['TranslatedList'])) {
            //批量翻译 返回数组
            $data = [];
            foreach ($ret['TranslatedList'] as $value) {
                if ($value['code'] === 200) {
                    $data[$value['index']] = $value['translated'];
                } else {
                    $isSuccess      = false;
                    $ret['Message'] = 'code:' . $value['code'] . ',msg:' . $value['errorMsg'];
                    break;
                }
            }
        }

        return [
            $isSuccess,
            "[{$sourceLanguage} => {$targetLanguage}]" . ($isSuccess ? 'Translated' : $ret['Message']),
            $data,
        ];
    }

}