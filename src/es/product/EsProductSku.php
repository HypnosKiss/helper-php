<?php

namespace Sweeper\HelperPhp\es\product;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/14 9:19
 * @Path \es\product\EsProductSku
 */
class EsProductSku extends EsProductBase
{

    /** @var string 索引 index / 数据库 */
    public const INDEX = 'product_sku';

    /** @var string 类型 type / 表 */
    public const TYPE = '_doc';

    //申报词类型  declare_items.type

    /** @var int 中文申报名 */
    public const DECLARE_TYPE_CN = 1;

    /** @var int 英文申报名 */
    public const DECLARE_TYPE_EN = 2;

    /** @var int 海关编码 */
    public const DECLARE_TYPE_CODE = 3;

    /** @var int 材质 */
    public const DECLARE_TYPE_MATERIAL = 4;

    /** @var int 用途 */
    public const DECLARE_TYPE_PURPOSE = 5;

    /** @var int 重量、尺寸类型 产品本身的尺寸/重量 */
    public const NET = 1;

    /** @var int 包装后的尺寸/重量 */
    public const PACK = 2;

    //图片类型 image_items.type

    /** @var int 产品描述图 */
    public const IMAGE_TYPE_DESC = 1;

    /** @var int 尺寸图 */
    public const IMAGE_TYPE_SIZE = 2;

    /** @var int 白底图 */
    public const IMAGE_TYPE_WHITE = 3;

    /** @var int 其他图 */
    public const IMAGE_TYPE_OTHER = 4;

    /** @var int 临时图 */
    public const IMAGE_TYPE_TMP = 5;

    /** @var int 场景图 */
    public const IMAGE_TYPE_SCENE = 6;

    /** @var int[] 图片类型排序定义 */
    public const IMAGE_TYPE_SORT = [
        self::IMAGE_TYPE_WHITE,
        self::IMAGE_TYPE_SCENE,
        self::IMAGE_TYPE_DESC,
        self::IMAGE_TYPE_SIZE,
        self::IMAGE_TYPE_OTHER,
        self::IMAGE_TYPE_TMP
    ];

}
