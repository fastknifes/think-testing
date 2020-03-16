<?php
// +----------------------------------------------------------------------
// | TestRpcBase 单元测试基类，支持think-swoole的rpc
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use think\console\Output;
use think\facade\App;

class TestRpcBase extends TestBase
{

    use RpcClientTrait;

    /**
     * rpc加载状态
     * @var bool
     */
    protected static $loadRpc = false;

    /**
     * step 2
     * 执行单元测试前执行此方法
     */
    protected function setUp()
    {
        $this->output = new Output();
        //rpc客户端准备
        if (false === self::$loadRpc && file_exists($rpc = App::getBasePath() . 'rpc.php')) {
            $this->prepareRpcClient();
            self::$loadRpc = true;
        }
    }
}