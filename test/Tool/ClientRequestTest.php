<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/24 18:24
 */

namespace Sweeper\HelperPhp\Test\Tool;

use Concat\Http\Middleware\Logger;
use PHPUnit\Framework\TestCase;
use Sweeper\GuzzleHttpRequest\Request;
use Sweeper\HelperPhp\Tool\ClientRequest;

class ClientRequestTest extends TestCase
{

    /**
     * 使用请求参数
     * User: Sweeper
     * Time: 2023/3/10 14:11
     * @param       $platform
     * @param       $accountId
     * @param array $params
     * @param array $requestParams
     * @return array
     */
    public function withRequestParams($platform, $accountId, array $params = [], array $requestParams = []): array
    {
        $requestParams         = array_replace_recursive($requestParams, [
            'platform'   => $platform,
            'account_id' => $accountId,
            'params'     => json_encode($params),
            'partner_id' => 390627,
            'timestamp'  => time(),
        ]);
        $requestParams['sign'] = ClientRequest::generateSign($requestParams, 'QKU5pHqmxXnSRkoh8yZvzwu7rEeaNYBMLIiW9f41JAcsVg3ODjlbt0G2TdPCF6');

        return $requestParams;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/24 18:42
     * @doc https://www.bejson.com/knownjson/webInterface/
     * @return void
     */
    public function testRequest(): void
    {
        $order_id      = '5271164584257';
        $requestParams = $this->withRequestParams('shopify', 5001728, ['order_id' => $order_id]);
        $options       = ClientRequest::withRetry(ClientRequest::withLog(ClientRequest::withTap(ClientRequest::withDelay(ClientRequest::withDebug()))));
        $params        = ClientRequest::withFormParams($requestParams);
        $rs            = ClientRequest::instance()->get('https://suggest.taobao.com/sug?code=utf-8&q=%E5%8D%AB%E8%A1%A3&callback=cb', $params, $options);
        dump($rs);
        $rs = ClientRequest::instance()->get('http://api.map.baidu.com/telematics/v3/weather?location=深圳&output=json&ak=5slgyqGDENN7Sy7pw29IUvrZ', $params, $options);
        dump($rs);

        $this->assertInstanceOf(Request::class, ClientRequest::instance());
    }

    public function testClientRequest(): void
    {
        $rs = ClientRequest::instance()
                           ->setSecretKey('QKU5pHqmxXnSRkoh8yZvzwu7rEeaNYBMLIiW9f41JAcsVg3ODjlbt0G2TdPCF6')
                           ->doRequest('http://middleware.tenflyer.com', 'v1/tiktok/order/get_order_detail',
                               [

                                   'partner_id' => 390627,
                                   'timestamp'  => time(),
                               ],
                               [
                                   'platform'   => 'tiktok',
                                   'account_id' => 36675,
                                   'orderIds'   => ["577954482659428384"],
                               ], ClientRequest::instance()->addOptions([], null, true));

        dump($rs);

        $this->assertInstanceOf(Request::class, ClientRequest::instance());
    }

    public function testClientRequestVersion(): void
    {
        $this->assertEquals('v2', ClientRequest::instance()->v2()->getVersion());
    }

    public function testClientRequestLoggerMiddleware(): void
    {
        $this->assertInstanceOf(Logger::class, ClientRequest::getLoggerMiddleware());
    }

}
