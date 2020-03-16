<?php
// +----------------------------------------------------------------------
// | Test
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing\command;

use PHPUnit\TextUI\Command as TextUICommand;
use PHPUnit\Util\Blacklist;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Session;

class Test extends Command
{
    /**
     * 配置指令
     * @return void
     */
    public function configure()
    {
        $this->setName('unit')
            ->setDescription('运行单元测试')
            ->ignoreValidationErrors();
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws \ReflectionException
     */
    public function handle(Input $input, Output $output)
    {
        Session::init();
        $argv = $_SERVER['argv'];
        array_shift($argv);
        array_shift($argv);
        array_unshift($argv, 'phpunit');
        Blacklist::$blacklistedClassNames = [];
        $code = (new TextUICommand())->run($argv, false);
        return $code;
    }

}
