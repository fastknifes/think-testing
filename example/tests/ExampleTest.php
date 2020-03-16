<?php
// +----------------------------------------------------------------------
// | ExampleTest
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace tests;

class ExampleTest extends TestCase
{

    public function testBasicExample()
    {
        $this->visit('/')->see('ThinkPHP');
    }
}