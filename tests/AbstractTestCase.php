<?php
namespace Tests;

use ReflectionMethod;
use ReflectionObject;
use ReflectionException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * 测试基类
 *
 * @author tianhaishen
 */
abstract class AbstractTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * 通过反射获取对象的`private`或者`protected`方法
     *
     * @param Object $obj 对象实例
     * @param string $name 方法名称
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected function getMethod($obj, $name)
    {
        $refObj = new ReflectionObject($obj);
        $method = $refObj->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * 执行一个对象的`private`或者`protected`方法
     *
     * @param Object $obj 对象实例
     * @param string $name 方法名称
     * @param array $args 方法参数
     * @return mixed
     * @throws ReflectionException
     */
    protected function invokeMethod($obj, $name, $args)
    {
        $method = $this->getMethod($obj, $name);
        return $method->invokeArgs($obj, $args);
    }
}
