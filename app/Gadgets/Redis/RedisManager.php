<?php

namespace App\Gadgets\Redis;


use Illuminate\Redis\Connectors\PhpRedisConnector;

class RedisManager extends \Illuminate\Redis\RedisManager
{

    /**
     * Resolve the given connection by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Redis\Connections\Connection
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        $options = $this->config['options'] ?? [];

        if (isset($this->config[$name])) {
            return $this->connector()->connect(self::randomConfig($this->config[$name]), $options, $name);
        }

        if (isset($this->config['clusters'][$name])) {
            return $this->resolveCluster($name);
        }

        throw new \InvalidArgumentException(
            "Redis connection [{$name}] not configured."
        );
    }

    /**
     * 针对哨兵配置 进行随机处理
     *
     * @param $config
     *
     * @return mixed
     */
    private static function randomConfig($config)
    {
        if (!$config) return $config;
        $options = $config['options'] ?? [];
        if (isset($config[0]) && isset($options['replication']) && $options['replication'] == 'sentinel') {
            unset($config['options']);
            shuffle($config);
            $config['options'] = $options;
        }

        return $config;
    }

    /**
     * Resolve the given cluster connection by name.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function resolveCluster($name)
    {
        $clusterOptions = $this->config['clusters']['options'] ?? [];

        return $this->connector()->connectToCluster(
            $this->config['clusters'][$name], $clusterOptions, $this->config['options'] ?? [], $name
        );
    }


    /**
     * Get the connector instance for the current driver.
     *
     * @return \Illuminate\Redis\Connectors\PhpRedisConnector|PredisConnector
     */
    protected function connector()
    {
        switch ($this->driver) {
            case 'predis':
                return new PredisConnector;
            case 'phpredis':
                return new PhpRedisConnector;
        }
    }
}
