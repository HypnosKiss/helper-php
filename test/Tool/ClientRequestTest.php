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
use Sweeper\GuzzleHttpRequest\ServiceRequest;
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
        $rs = ClientRequest::instance()->get('https://suggest.taobao.com/sug?code=utf-8&q=%E5%8D%AB%E8%A1%A3&callback=cb');
        dump($rs);
        $rs = ClientRequest::instance()->get('http://api.map.baidu.com/telematics/v3/weather?location=深圳&output=json&ak=5slgyqGDENN7Sy7pw29IUvrZ');
        dump($rs);

        $this->assertInstanceOf(Request::class, ClientRequest::instance());
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/27 18:44
     * @return void
     */
    public function testServiceRequest(): void
    {
        $requestParams = $this->withRequestParams('tiktok', 36675, ['orderIds' => ['577954482659428384']]);
        $options       = ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug()))));
        $params        = ServiceRequest::withFormParams($requestParams);
        $rs            = ServiceRequest::instance()->post('http://middleware.tenflyer.com/v1/tiktok/order/get_order_detail', $params, $options);
        dump($rs);
        $this->assertInstanceOf(Request::class, ServiceRequest::instance());
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/27 18:48
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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
                               ], [], ClientRequest::instance()->addOptions([], null, true));

        dump($rs);

        $this->assertInstanceOf(Request::class, ClientRequest::instance());
    }

    public function testClientDoRequest(): void
    {
        $requestParams = $this->withRequestParams('tiktok', 36675, ['orderIds' => ['577954482659428384']]);
        $params        = ServiceRequest::withFormParams($requestParams);
        $options       = ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug()))));
        $rs            = ServiceRequest::instance()
                                       ->setMethod(ServiceRequest::POST)
                                       ->setUri('http://middleware.tenflyer.com/v1/tiktok/order/get_order_detail')
                                       ->setParams($params)
                                       ->setOptions($options)
                                       ->do();

        dump($rs);

        $this->assertInstanceOf(Request::class, ServiceRequest::instance());
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
