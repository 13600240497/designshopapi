<?php

namespace App\Gadgets\Redis;


use Illuminate\Support\Arr;
use Predis\Client;

class PredisConnector /** extends \Illuminate\Redis\Connectors\PredisConnector  */
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param  array $config
     * @param  array $options
     *
     * @return PredisConnection
     * @throws
     */
    public function connect(array $config, array $options, $name)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );

        try {
            return new PredisConnection(new Client($config, $formattedOptions), $name);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param  array $config
     * @param  array $clusterOptions
     * @param  array $options
     *
     * @return PredisConnection
     * @throws
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options, $name)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        try {
            return new PredisConnection(new Client(array_values($config), array_merge(
                $options, $clusterOptions, $clusterSpecificOptions
            )), $name);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
