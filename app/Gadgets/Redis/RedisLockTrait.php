<?php

namespace App\Gadgets\Redis;


use Closure;

trait RedisLockTrait
{
    /**
     * 锁定
     * @param bool $lockCondition 锁定条件
     * @param array $lockKeys 锁定key
     * @param int $lockSeconds 锁定时间
     * @return bool
     */
    public function lock(bool $lockCondition,$lockKeys =[],$lockSeconds = 60)
    {
        $lockKeys = $this->generateLockKeys($lockKeys);
        $redis = app('redis');
        $lockData = $redis ? array_filter($redis->mget($lockKeys)) : null;
        if (filled($lockData)) {
            return true;
        }
        if ($lockCondition) {
            foreach ($lockKeys as $key) {
                $lockSeconds && $redis && $redis->setex($key, $lockSeconds, 1);
            }
            return true;
        }

        return false;
    }

    /**
     * 解除锁定
     * @param array $lockKeys
     * @param Closure $callback 解除锁定执行的回调函数
     * @return Closure
     */
    public function unLock($lockKeys,Closure $callback)
    {
        $redis = app('redis');
        $redis && $redis->del($this->generateLockKeys($lockKeys));
        return $callback();
    }

    /**
     * 组装锁定key
     * @param $lockKeys
     * @return array
     */
    protected function generateLockKeys($lockKeys)
    {
        return array_map(function ($v) {
            return $v.':locked';
        },$lockKeys);
    }
}
