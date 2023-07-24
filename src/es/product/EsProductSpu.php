<?php

namespace Sweeper\HelperPhp\es\product;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/14 9:20
 * @Path \es\product\EsProductSpu
 */
class EsProductSpu extends EsProductBase
{

    /** @var string 索引 index / 数据库 */
    public const INDEX = 'product_spu';

    /** @var string 类型 type / 表 */
    public const TYPE = '_doc';

    //产品来源 source

    /** @var int 自开发产品 */
    public const SOURCE_DEV = 1;

    /** @var int 跟卖产品 */
    public const SOURCE_FOLLOW = 2;

    /** @var int 分销产品 */
    public const SOURCE_DISTRIBUTE = 3;

    /** @var int 1688开发 */
    public const SOURCE_1688 = 4;

    /** @var int 海外仓开发 */
    public const SOURCE_ABROAD = 5;

}
