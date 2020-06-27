<?php
// +----------------------------------------------------------------------
// | RpcClientTrait
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use rpc\client\interfaces\Connector;
use rpc\client\Gateway;
use rpc\client\interfaces\ParserInterface;
use rpc\client\JsonParser;
use rpc\client\Packer;
use rpc\client\Proxy;
use Swoole\Coroutine;
use think\Exception;
use think\facade\App;
use think\facade\Config;
use Swoole\Client;

trait RpcClientTrait
{
    /**
     * Rpc接口列表
     * @var array
     */
    protected $rpcServices = [];

    /**
     * 准备RPC客户端
     * @return void
     */
    protected function prepareRpcClient()
    {
        //引入rpc接口文件
        if (file_exists($rpc = App::getBasePath() . 'rpc.php')) {
            $rpcServices = (array)include $rpc;
            $this->rpcServices = array_merge($rpcServices, $this->rpcServices);
        }
        $this->bindRpcClientPool();
    }

    /**
     * 绑定rpc客户端
     * @return void
     */
    protected function bindRpcClientPool()
    {
        //绑定rpc接口
        try {
            foreach ($this->rpcServices as $name => $abstracts) {
                $parserClass = Config::get("swoole.rpc.client.{$name}.parser", JsonParser::class);
                /**
                 * @var ParserInterface $parser
                 */
                $parser = App::make($parserClass);
                $gateway = new Gateway($this->createRpcConnector($name), $parser);
                foreach ($abstracts as $abstract) {
                    App::bind(
                        $abstract,
                        function () use ($name, $abstract, $gateway) {
                            return App::invokeClass(Proxy::getClassName($name, $abstract), [$gateway]);
                        }
                    );
                }
            }
        } catch (\Exception | \Throwable $e) {
        }
    }

    /**
     * 创建连接器
     * @param string $name
     * @return Connector|__anonymous@2171
     * @throws Exception
     */
    protected function createRpcConnector($name)
    {
        $clientConfig = Config::get("swoole.rpc.client.{$name}", null);
        if (empty($clientConfig)) {
            throw new Exception('Rpc客户端配置信息错误');
        }
        return new class($clientConfig) implements Connector {

            /**
             * 客户端配置
             * @var array
             */
            protected $clientConfig;

            /**
             *  constructor.
             * @param array $clientConfig
             */
            public function __construct(array $clientConfig)
            {
                $this->clientConfig = $clientConfig;
            }

            /**
             * @param \Generator|string $data
             * @return mixed|string
             */
            public function sendAndRecv($data)
            {
                if (!$data instanceof \Generator) {
                    $data = [$data];
                }
                $class = Coroutine::getCid() > -1 ? Coroutine\Client::class : Client::class;
                /**
                 * @var Client $client
                 */
                $client = new $class(SWOOLE_SOCK_TCP);
                $config = array_merge(
                    $this->clientConfig,
                    [
                        'open_length_check' => true,
                        'package_length_type' => Packer::HEADER_PACK,
                        'package_length_offset' => 0,
                        'package_body_offset' => 8,
                    ]
                );
                $client->set($config);
                //连接
                if (!$client->connect($this->clientConfig['host'], $this->clientConfig['port'], $this->clientConfig['timeout'] ?? 5)) {
                    throw new Exception(
                        sprintf('Rpc连接失败 host=%s port=%d', $this->clientConfig['host'], $this->clientConfig['port'])
                    );
                }
                try {
                    foreach ($data as $string) {
                        if (!$client->send($string)) {
                            throw new Exception(swoole_strerror($client->errCode), $client->errCode);
                        }
                    }
                    $response = $client->recv();
                    if ($response === false || empty($response)) {
                        throw new Exception(swoole_strerror($client->errCode), $client->errCode);
                    }
                } finally {
                    $client->close();
                }
                return $response;
            }
        };
    }
}
