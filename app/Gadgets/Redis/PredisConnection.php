<?php

namespace App\Gadgets\Redis;


use Predis\Client;

class PredisConnection extends \Illuminate\Redis\Connections\PredisConnection
{
    private $connectionName;

    public function __construct(Client $client, $name)
    {
        parent::__construct($client);
        $this->connectionName = $name;
    }

    public function __call($method, $parameters)
    {
        try {
            $result = parent::__call($method, $parameters);
        } catch (\Throwable $e) {
            throw $e;
        }

        return $result;
    }
}
