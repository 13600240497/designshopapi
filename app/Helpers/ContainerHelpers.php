<?php
namespace App\Helpers;

/**
 * 方便获取容器相关实现方法
 *
 * @author TianHaisen
 */
class ContainerHelpers
{

    /**
     * 获取Redis key 对象
     *
     * @return \App\Gadgets\Rdkey\Rdkey
     */
    public static function getRedisKey()
    {
        return app('rdkey');
    }

    /**
     * 获取Predis名称为cache的实例对象
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public static function getPredisResolveCache()
    {
        return app('predis')->resolve('cache');
    }
}