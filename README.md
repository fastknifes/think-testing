
### 介绍

单元测试

使用的时候直接在项目目录下执行 `php think unit`


> 该项目改自 shuipf/think-testing

### 相关手册

`配置实例：https://github.com/top-think/think`

`框架核心库：https://github.com/top-think/framework`



### 用例

#### 配置
请在项目根目录下创建 tests目录，并创建phpunit.xml,内容如下：
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Application Test Suite">
            <directory suffix=".php">./tests/</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">app/</directory>
        </whitelist>
    </filter>
</phpunit>
```

#### 基本格式
- 测试类命名： 类名 + Test ， eg FooClassTest
- 测试方法命名： test + 方法名 , eg testFoo
> 也可以使用注释 @test 来标注需要测试的方法

```php
class SampleTest extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true, 'This should already work.');
    }

    /**
     * @test
     */
    public function something()
    {
        $this->assertTrue(true, 'This should already work.');
    }
}
```

#### 常用命令
- 过滤文件
  php think unit --filter ***
```sh 
--filter 'TestNamespace\\TestCaseClass::testMethod'

--filter 'TestNamespace\\TestCaseClass'

--filter TestNamespace

--filter TestCaseClass

--filter testMethod
```
- 分组
  php think unit --group ***
  可以用 @group 标注来标记某个case属于一个或多个组，就像这样：
```php
class MyTest extends TestCase{
    /**
     * @group specification
     */
    public function testSomething(){
    }

    /**
     * @group regresssion
     * @group bug2204
     */
    public function testSomethingElse(){
    }
}
```
#### 测试依赖 (@depends)
有一些测试方法需要依赖于另一个测试方法的返回值，此时需要使用测试依赖。测试依赖
通过注释 @depends 来标记。
下列中， depends 方法的 return 值作为 testConsumer 的参数传入
```php
class MultipleDependenciesTest extends TestCase
{
    public function testProducerFirst()
    {
        $this->assertTrue(true);
        return 'first';
    }

    public function testProducerSecond()
    {
        $this->assertTrue(true);
        return 'second';
    }

    /**
     * @depends testProducerFirst
     * @depends testProducerSecond
     */
    public function testConsumer($a, $b)
    {
        $this->assertSame('first', $a);
        $this->assertSame('second', $b);
    }
}
```

#### 数据提供器 (@dataProvider)
在依赖中，所依赖函数的返回值作为参数传入测试函数。除此之外，我们也可以用数据提供器来定义传入的数据。
```php
class DataTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function testAdd($a, $b, $expected)
    {
        $this->assertSame($expected, $a + $b);
    }

    public function additionProvider()
    {
        return [
            'adding zeros' => [0, 0, 0], // 0 + 0 = 0 pass
            'zero plus one' => [0, 1, 1], // 0 + 1 = 1 pass
            'one plus zero' => [1, 0, 1], // 1 + 0 = 1 pass
            'one plus one' => [1, 1, 2], // 1 + 1 = 2 pass
        ];
    }
}
```

#### 测试异常 (expectException)
需要在测试方法的开始处声明断言，然后执行语句。而不是调用后再声明
也可以通过注释来声明 @expectedException, @expectedExceptionCode,
@expectedExceptionMessage, @expectedExceptionMessageRegExp
```php

class ExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(\Exception::class);

        throw new \Exception('test');
    }

    /**
     * @throws \Exception
     * @test
     * @expectedException \Exception
     */
    public function exceptionExpect()
    {
        throw new \Exception('test');
    }
}
```