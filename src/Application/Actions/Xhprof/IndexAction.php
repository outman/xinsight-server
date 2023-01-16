<?php

namespace App\Application\Actions\Xhprof;

use Psr\Http\Message\ResponseInterface as Response;

class IndexAction extends XhprofAction
{
    /**
     * @return Response
     */
    protected function action(): Response
    {
        try {
            $filterParams = [
                'uri' => $this->getParam('uri', ''),
                'domain' => $this->getParam('domain', ''),
                'minCost' => $this->parseCostTime($this->getParam('minCost', 0)),
                'maxCost' => $this->parseCostTime($this->getParam('maxCost', 0)),
                'sort' => (int) $this->getParam('sort', 0),
                'page' => (int) $this->getParam('page', 1),
            ];
            $data = $this->xhprofRepository->searchByCriteria($filterParams);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $data = [
                'pagination' => (object) [],
                'data' => (object) []
            ];
        }
        return $this->respondWithData($data);
    }

    /**
     * @param $cost
     * @return float|int
     */
    protected function parseCostTime($cost)
    {
        if (stripos($cost, 'ms')) {
            $cost = strtr($cost, ['ms' => '']);
            if (is_numeric($cost)) {
                return $cost * 1000;
            }
        } elseif (stripos($cost, 's')) {
            $cost = strtr($cost, ['s' => '']);
            if (is_numeric($cost)) {
                return $cost * 1000 * 1000;
            }
        }
        return 0;
    }
}