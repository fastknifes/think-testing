<?php
// +----------------------------------------------------------------------
// | AssertionsTrait
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use PHPUnit\Framework\Assert as PHPUnit;
use think\facade\Session;
use think\response\View;

trait AssertionsTrait
{
    /**
     * 断言响应状态
     * @return void
     */
    public function assertResponseOk()
    {
        $actual = $this->response->getCode();
        //断言是否为真
        PHPUnit::assertTrue(200 == $actual, "Expected status code 200, got {$actual}.");
    }

    /**
     * 断言响应状态
     * @param $code
     */
    public function assertResponseStatus($code)
    {
        $actual = $this->response->getCode();
        //断言属性值是否相等
        PHPUnit::assertEquals($code, $actual, "Expected status code {$code}, got {$actual}.");
    }

    /**
     * @param $key
     * @param null $value
     */
    public function assertViewHas($key, $value = null)
    {
        if (is_array($key)) {
            $this->assertViewHasAll($key);
        } else {
            if (!$this->response instanceof View) {
                PHPUnit::assertTrue(false, 'The response was not a view.');
            } else {
                if (is_null($value)) {
                    PHPUnit::assertArrayHasKey($key, $this->response->getVars());
                } else {
                    PHPUnit::assertEquals($value, $this->response->getVars($key));
                }
            }
        }
    }

    /**
     * @param array $bindings
     */
    public function assertViewHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value);
            } else {
                $this->assertViewHas($key, $value);
            }
        }
    }

    /**
     * @param $key
     */
    public function assertViewMissing($key)
    {
        if (!$this->response instanceof View) {
            PHPUnit::assertTrue(false, 'The response was not a view.');
        } else {
            PHPUnit::assertArrayNotHasKey($key, $this->response->getVars());
        }
    }

    /**
     * @param $uri
     * @param array $params
     */
    public function assertRedirectedTo($uri, $params = [])
    {
        $this->assertInstanceOf('think\response\Redirect', $this->response);
    }

    /**
     * @param $key
     * @param null $value
     */
    public function assertSessionHas($key, $value = null)
    {
        if (is_array($key)) {
            $this->assertSessionHasAll($key);
        } else {
            if (is_null($value)) {
                PHPUnit::assertTrue(Session::has($key), "Session missing key: $key");
            } else {
                PHPUnit::assertEquals($value, Session::get($key));
            }
        }
    }

    /**
     * @param array $bindings
     */
    public function assertSessionHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);
            } else {
                $this->assertSessionHas($key, $value);
            }
        }
    }
}
