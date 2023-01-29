<?php

namespace App\Infrastructure\Persistence\Xhprof;

use App\Application\Library\Pagination;
use App\Infrastructure\Connector\MongoConnectorManager;
use Psr\Log\LoggerInterface;

class XhprofMongoRepository implements \App\Domain\Xhprof\XhprofRepository
{
    protected MongoConnectorManager $mongo;
    protected LoggerInterface $logger;

    public function __construct(MongoConnectorManager $mongo, LoggerInterface $logger)
    {
        $this->mongo = $mongo;
        $this->logger = $logger;
    }


    /**
     * @param array $filterParams
     * @return array
     */
    public function searchByCriteria(array $filterParams = []): array
    {
        $collection = $this->mongo->selectCollection();
        $filter = $this->parseQuery($filterParams);
        $rows = $collection->countDocuments($filter);
        $pagination = new Pagination($filterParams['page'], $rows);

        $options = [
            'skip' => $pagination->getSkip(),
            'limit' => $pagination->getPageSize(),
        ];

        if ($filterParams['sort'] !== 0) {
            $options['sort'] = [
                'wt' => $filterParams['sort'],
            ];
        } else {
            $options['sort'] = [
                'request_time' => -1,
            ];
        }

        $this->logger->info(json_encode([
            'query' => $filter,
            'options' => $options,
        ]));

        $cursor = $collection->find($filter, $options);
        $data = [];
        foreach ($cursor as $item) {
            $data[] = $item;
        }

        return [
            'pagination' => $pagination->jsonSerialize(),
            'data' => $data,
        ];
    }

    /**
     * @param string $id
     * @return array|object|null
     */
    public function findXhprofOfId(string $id)
    {
        $collection = $this->mongo->selectCollection();
        return $collection->findOne([
            "_id" => new \MongoDB\BSON\ObjectId($id),
        ]);
    }

    /**
     * @param array $filterParams
     * @return array
     */
    private function parseQuery(array $filterParams = []): array
    {
        $query = [];
        if ($filterParams['uri']) {
            $query['url'] = $filterParams['uri'];
        }

        if ($filterParams['domain']) {
            $query['server_name'] = $filterParams['domain'];
        }

        if ($filterParams['minCost']) {
            $query['wt'] = [
                '$gt' => $filterParams['minCost'],
            ];
        }

        if ($filterParams['maxCost']) {
            $query['wt'] = [
                '$lt' => $filterParams['maxCost'],
            ];
        }
        return $query;
    }
}