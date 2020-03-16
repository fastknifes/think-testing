<?php
// +----------------------------------------------------------------------
// | ApplicationTrait
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use think\Db;
use think\facade\Session;

trait ApplicationTrait
{
    /**
     * session设置
     * @param array $data
     * @return $this
     */
    public function withSession(array $data)
    {
        foreach ($data as $key => $value) {
            Session::set($key, $value);
        }
        return $this;
    }

    /**
     * 清空session数据
     * @return void
     */
    public function clearSession()
    {
        Session::clear();
    }

    /**
     * @param string $table
     * @param array $data
     * @return $this
     */
    protected function seeInDatabase(string $table, array $data)
    {
        $count = Db::name($table)->where($data)->count();
        //当 $count 的值不大于 0 的值时报告错误，错误讯息由 $message 指定
        $this->assertGreaterThan(
            0, $count, sprintf(
                 'Unable to find row in database table [%s] that matched attributes [%s].', $table, json_encode($data)
             )
        );
        return $this;
    }

    /**
     * @param $table
     * @param array $data
     * @return $this
     */
    protected function notSeeInDatabase($table, array $data)
    {
        $count = Db::name($table)->where($data)->count();
        //当 $expected 和 $actual 这两个对象的属性值不相等时报告错误
        $this->assertEquals(
            0, $count, sprintf(
                 'Found unexpected records in database table [%s] that matched attributes [%s].', $table, json_encode($data)
             )
        );
        return $this;
    }
}
