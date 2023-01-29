<?php

namespace App\Infrastructure\Connector;

use MongoDB\Client;
use MongoDB\Collection;

class MongoConnectorManager
{
    /**
     * @var string
     */
    protected string $configureName = 'xhprof';

    /**
     * configure options
     * @var array|mixed
     */
    protected $configure = [];

    protected array $clients = [];

    /**
     * @param array $configure
     */
    public function __construct(array $configure = [])
    {
        $this->configure = $configure;
    }


    /**
     * @return Client
     */
    public function getMongoClient(): Client
    {

        if (empty($this->clients[$this->configureName])) {
            $this->clients[$this->configureName] = new Client(
                $this->configure[$this->configureName]['server'],
                $this->configure[$this->configureName]['options'],
                $this->configure[$this->configureName]['driverOptions']
            );
        }

        return $this->clients[$this->configureName];
    }

    /**
     * @return Collection
     */
    public function selectCollection(): Collection
    {
        return $this->getMongoClient()
            ->selectDatabase($this->configure[$this->configureName]['database'])
            ->selectCollection($this->configure[$this->configureName]['collection']);
    }
}
