<?php
// +----------------------------------------------------------------------
// | TestBase 单元测试基类
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use think\console\Output;
use think\facade\App;
use think\facade\Request;

class TestBase extends TestCase
{

    use RpcClientTrait;

    /**
     * rpc加载状态
     * @var bool
     */
    protected static $loadRpc = false;

    /**
     * 基础路径
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var Output
     */
    protected $output;

    /**
     * 单页常规GET访问测试
     * @param string $urlPath 请求路径
     * @param array $headers
     */
    protected function pageGet($urlPath, $headers = [])
    {
        $this->get($urlPath, $headers);
        if (!in_array($this->response->getCode(), [200, 301])) {
            $this->output->error("{$urlPath} 状态异常");
            $this->errorCapture();
        }
        //断言是否为 Response 对象
        if ($this->response instanceof \think\Response) {
            $this->responseEquals();
        }
        //断言访问状态
        $this->assertEquals(200, $this->response->getCode(), $this->error("GET 请求状态不正确，不是预期的 200"));
        //是否是$this->error错误页
        $this->isErrorContent();
    }

    /**
     * 单页常规Post访问测试
     * @param string $urlPath 请求路径
     * @param array $data 提交数据
     * @param array $headers
     */
    protected function pagePost($urlPath, $data = [], $headers = [])
    {
        $this->post($urlPath, $data, $headers);
        if (!in_array($this->response->getCode(), [200, 301])) {
            $this->errorCapture();
        }
        //断言是否为 Response 对象
        if ($this->response instanceof \think\Response) {
            $this->responseEquals();
        }
        //断言访问状态
        $this->assertEquals(200, $this->response->getCode(), $this->error("GET 请求状态不正确，不是预期的 200"));
        //是否是$this->error错误页
        $this->isErrorContent();
    }

    /**
     * 断言是否是json格式返回
     * @return bool
     */
    protected function isJsonRetrun()
    {
        if (empty($this->response)) {
            return false;
        }
        //获取地址
        $url = Request::url(true);
        json_decode($this->response->getContent());
        $this->assertTrue(json_last_error() ? false : true, " 地址 {$url} 返回的数据不是JSON格式数据");
    }

    /**
     * 断言页面是否为$this->error页面
     * @return bool
     */
    protected function isErrorContent()
    {
        if (empty($this->response)) {
            return true;
        }
        //获取地址
        $url = Request::url(true);
        $content = $this->response->getContent();
        $systemMessage = strpos($content, '.system-message .success,.system-message .error');
        $message = "地址 {$url} 页面提示错误";
        if (false !== $systemMessage) {
            preg_match("/<p class=\"error\">(.*?)<\/p><p class=\"detail\">/", $content, $m);
            if (isset($m[1])) {
                $message .= " ，错误提示：{$m[1]}";
            }
        }
        $this->assertEquals(false, $systemMessage, $this->error($message));
    }

    /**
     * 致命错误捕获
     */
    protected function errorCapture()
    {
        $url = Request::url(true);
        //使用正则捕获错误信息
        preg_match('/<h1>(.*?)<\/h1>/i', $this->response->getContent(), $errorMsg);
        $this->output->error("出现致命错误，具体错误信息：\n请求地址：{$url}\n请求状态：{$this->response->getCode()}\n捕获到的错误信息：" . (isset($errorMsg[1]) ? $errorMsg[1] : ''));
    }

    /**
     * Response 对象 断言
     */
    protected function responseEquals()
    {
        $data = $this->response->getData();
        if (is_array($data)) {
            if (isset($data['code'])) {
                $this->assertEquals(1, $data['code'], $this->error('返回Response对象：' . var_export($data, true)));
            }
        }
    }

    /**
     * 拼装错误信息
     * @param string 错误信息 $msg
     * @return string
     */
    protected function error($msg)
    {
        $url = Request::url(true);
        $error = "访问地址：{$url}\n";
        $error .= "错误信息：\n";
        $error .= $msg;
        return $error;
    }

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

    /**
     * step 3
     * 执行测试后调用此方法。
     */
    protected function tearDown()
    {

    }

    /**
     * step 1
     * 在运行此测试类的第一个测试之前调用此方法
     */
    public static function setUpBeforeClass()
    {

    }

    /**
     * step 4
     * 在运行此测试类的最后一次测试后调用此方法
     */
    public static function tearDownAfterClass()
    {

    }
}