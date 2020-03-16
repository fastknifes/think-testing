<?php
// +----------------------------------------------------------------------
// | TestCase
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    use ApplicationTrait, AssertionsTrait, CrawlerTrait;

    /**
     * 基础路径
     * @var string
     */
    protected $baseUrl = '';
}
