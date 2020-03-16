<?php
// +----------------------------------------------------------------------
// | Service 注册服务
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use think\testing\command\Test;

class Service extends \think\Service
{

    /**
     * 执行服务
     * @return void
     */
    public function boot()
    {
        $this->commands(
            [
                Test::class,
            ]
        );
    }

}
