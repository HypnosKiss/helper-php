<?php

namespace Sweeper\HelperPhp\Tool;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sweeper\GuzzleHttpRequest\ServiceRequest;

class ClientRequest extends ServiceRequest
{

    /** @var int API 成功 Code */
    public const CODE_API_SUCCESS = 0;

    /** @var int API 失败 Code */
    public const CODE_API_FAILURE = 1;

    /** @var int 成功 Code */
    public const CODE_SUCCESS = 0;

    /** @var int 失败 Code */
    public const CODE_FAILURE = 1;

    /** @var string 密钥 */
    protected $secretKey = '';

    /** @var int 成功 CODE */
    protected $successCode = 200;

    public function getSecretKey(): string
    {
        return $this->secretKey ?: $this->getConfig('secretKey');
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * 使用指定选项
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/23 17:22
     * @param array         $options
     * @param callable|null $handler
     * @param bool          $registerLog
     * @param callable|null $logMiddleware
     * @return array
     */
    public function addOptions(array $options = [], callable $handler = null, bool $registerLog = false, callable $logMiddleware = null): array
    {
        // 创建 Handler
        if (isset($options['handler']) && $options['handler'] instanceof HandlerStack) {
            $handlerStack = $options['handler'];
        } else {
            $handlerStack = HandlerStack::create($handler);
        }

        // 附带请求头信息
        $handlerStack->push(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-Middleware-Request-Time', microtime(true));
        }), 'Middleware::mapRequest');

        // 附带响应头信息
        $handlerStack->push(Middleware::mapResponse(function(ResponseInterface $response) {
            // Make sure that the content of the body is available again.
            // $contents = $response->getBody()->getContents() ?? '';
            $response->getBody()->rewind();

            return $response->withHeader('X-Middleware-Response-Time', microtime(true));
        }), 'Middleware::mapResponse');

        // 在发送请求之前和之后调用回调的中间件
        $handlerStack->push(Middleware::tap(function(RequestInterface $request, array $options) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' Before sending the request', PHP_EOL;
            }
        }, function(RequestInterface $request, array $options, PromiseInterface $response) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' After receiving the response', PHP_EOL;
            }
        }), 'Middleware::tap');

        // 创建日志中间件
        // 先入后出，执行后必须重置响应内容，否则会导致获取不到响应内容
        if ($registerLog) {
            $handlerStack->push($this->getLoggerMiddleware(), 'Middleware::log');
        }

        $options['handler'] = $handlerStack;
        $options['debug']   = PHP_SAPI === 'cli';

        return $options;
    }

    /**
     * 发起请求
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/23 18:13
     * @param string|null $baseUrl
     * @param string      $extraUrl
     * @param array       $signParams
     * @param array       $params ['platform' => $platform, 'account_id' => $accountId, 'params' => json_encode($params)]
     * @param array       $options
     * @param string      $method
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest(string $baseUrl, string $extraUrl, array $signParams = [], array $params = [], array $options = [], string $method = 'POST')
    {
        $client      = new Client(['base_uri' => $baseUrl]);
        $requestInfo = $this->sign($signParams, ['platform' => $params['platform'] ?? '', 'account_id' => $params['account_id'] ?? 0, 'params' => json_encode($params)]);// 签名
        $body        = static::withJson($requestInfo);
        $body        = array_replace($body, ['connect_timeout' => 30, 'timeout' => 120], $options);
        $response    = $client->request($method ?: 'POST', $extraUrl, $body);
        if (!is_object($response)) {
            throw new \RuntimeException('网络响应超时，请重试！');
        }
        $contents = $response->getBody()->getContents();
        if (empty($contents)) {
            throw new \RuntimeException("请求响应异常,StatusCode：{$response->getStatusCode()}，Contents:" . $contents, $response->getStatusCode());
        }

        return json_decode($contents, true) ?: [];
    }

    /**
     * @param $signParams
     * @param $params
     * @return array
     */
    private function sign($signParams, $params): array
    {
        $params         = array_merge($signParams, $params);
        $sign           = $this->generateSign($params, $this->getSecretKey());
        $params['sign'] = $sign;

        return $params;
    }

}
