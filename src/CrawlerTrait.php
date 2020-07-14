<?php
// +----------------------------------------------------------------------
// | CrawlerTrait
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use think\facade\App;
use think\facade\Cookie;
use think\facade\Request;
use think\helper\Arr;
use think\helper\Str;
use think\Http;
use think\Response;

trait CrawlerTrait
{
    use InteractsWithPages;

    /**
     * 当前请求地址
     * @var string
     */
    protected $currentUri;

    /**
     * @var array
     */
    protected $serverVariables = [];

    /**
     * @var Response
     */
    protected $response;

    /**
     * GET请求
     * @param string $uri 请求路径
     * @param array $headers
     * @return $this
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $this->call('GET', $uri, [], [], [], $server);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return $this
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $this->call('POST', $uri, $data, [], [], $server);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return $this
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $this->call('PUT', $uri, $data, [], [], $server);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return $this
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);
        $this->call('DELETE', $uri, $data, [], [], $server);
        return $this;
    }

    /**
     * @param string $method 请求方式
     * @param string $uri 请求URL
     * @param array $parameters 参数
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param string $content
     * @return Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = '')
    {
        $this->currentUri = $this->prepareUrlForRequest($uri);
        //url分析
        $uriParse = parse_url($this->currentUri);
        $scheme = isset($uriParse['scheme']) ? $uriParse['scheme'] . '://' : '';
        $host = isset($uriParse['host']) ? $uriParse['host'] : '';
        $port = isset($uriParse['port']) ? ':' . $uriParse['port'] : '';
        $path = isset($uriParse['path']) ? $uriParse['path'] : '';
        //GET参数处理
        if ($method == 'GET' && !empty($uriParse['query'])) {
            parse_str($uriParse['query'] ?? '', $queryArr);
            $parameters = array_merge($queryArr, $parameters);
        }
        //server
        $server = array_replace($this->serverVariables, $server);
        $server['REQUEST_METHOD'] = $method;
        $server['QUERY_STRING'] = isset($uriParse['query']) ? $uriParse['query'] : '';
        $server['REQUEST_URI'] = $path;
        $server['PATH_INFO'] = $path;
        $server['HTTP_HOST'] = $host;

        /**
         * @var \think\Request $request ;
         */
        $request = App::make('request', [], true);
        App::delete('middleware');
        $request->withServer($server)
            ->setMethod($method)
            ->withGet($method == 'GET' ? $parameters : [])
            ->withPost($method == 'POST' ? $parameters : [])
            ->withCookie($cookies)
            ->withInput($content)
            ->withFiles($files)
            ->setBaseUrl($server['REQUEST_URI'])
            //->setUrl($this->currentUri . (!empty($server['QUERY_STRING']) ? '?' . $server['QUERY_STRING'] : ''))
            ->setPathinfo(ltrim($server['PATH_INFO'], '/'));
        /**
         * @var Http $http
         */
        $http = App::make('http', [], true);
        $response = $http->run($request);
        return $this->response = $response;
    }

    /**
     * @param null $data
     * @param bool $negate
     * @return $this|CrawlerTrait
     */
    public function seeJson($data = null, $negate = false)
    {
        if (is_null($data)) {
            $this->assertJson(
                $this->response->getContent(), "JSON was not returned from [{$this->currentUri}]."
            );
            return $this;
        }
        return $this->seeJsonContains($data, $negate);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function seeJsonEquals(array $data)
    {
        $actual = json_encode(
            Arr::sortRecursive(
                json_decode($this->response->getContent(), true)
            )
        );
        $this->assertEquals(json_encode(Arr::sortRecursive($data)), $actual);
        return $this;
    }

    /**
     * @param array $data
     * @param bool $negate
     * @return $this
     */
    protected function seeJsonContains(array $data, $negate = false)
    {
        $method = $negate ? 'assertFalse' : 'assertTrue';
        $actual = json_decode($this->response->getContent(), true);
        if (is_null($actual) || false === $actual) {
            return $this->fail('Invalid JSON was returned from the route. Perhaps an exception was thrown?');
        }
        $actual = json_encode(
            Arr::sortRecursive(
                (array)$actual
            )
        );
        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = $this->formatToExpectedJson($key, $value);
            $this->{$method}(
                Str::contains($actual, $expected),
                ($negate ? 'Found unexpected' : 'Unable to find') . " JSON fragment [{$expected}] within [{$actual}]."
            );
        }
        return $this;
    }

    /**
     * Format the given key and value into a JSON string for expectation checks.
     * @param $key
     * @param $value
     * @return false|string
     */
    protected function formatToExpectedJson($key, $value)
    {
        $expected = json_encode([$key => $value]);
        if (Str::startsWith($expected, '{')) {
            $expected = substr($expected, 1);
        }
        if (Str::endsWith($expected, '}')) {
            $expected = substr($expected, 0, -1);
        }
        return $expected;
    }

    /**
     * @param $controller
     * @return $this
     */
    protected function seeController($controller)
    {
        $this->assertEquals($controller, Request::controller());
        return $this;
    }

    /**
     * @param $action
     * @return $this
     */
    protected function seeAction($action)
    {
        $this->assertEquals($action, Request::action());
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    protected function seeStatusCode($status)
    {
        $this->assertEquals($status, $this->response->getCode());
        return $this;
    }

    /**
     * @param $headerName
     * @param null $value
     * @return $this
     */
    protected function seeHeader($headerName, $value = null)
    {
        $headers = $this->response->getHeader();
        $this->assertTrue(!empty($headers[$headerName]), "Header [{$headerName}] not present on response.");
        if (!is_null($value)) {
            $this->assertEquals(
                $headers[$headerName], $value,
                "Header [{$headerName}] was found, but value [{$headers[$headerName]}] does not match [{$value}]."
            );
        }
        return $this;
    }

    /**
     * @param $cookieName
     * @param null $value
     * @return $this
     */
    protected function seeCookie($cookieName, $value = null)
    {
        $exist = Cookie::has($cookieName);
        $this->assertTrue($exist, "Cookie [{$cookieName}] not present on response.");
        if (!is_null($value)) {
            $cookie = Cookie::get($cookieName);
            $this->assertEquals(
                $cookie, $value,
                "Cookie [{$cookieName}] was found, but value [{$cookie}] does not match [{$value}]."
            );
        }
        return $this;
    }

    /**
     * @param array $server
     * @return $this
     */
    protected function withServerVariables(array $server)
    {
        $this->serverVariables = $server;
        return $this;
    }

    /**
     * headers处理
     * @param array $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';
        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');
            if (!Str::startsWith($name, $prefix) && 'CONTENT_TYPE' != $name) {
                $name = $prefix . $name;
            }
            $server[$name] = $value;
        }
        return $server;
    }
}
