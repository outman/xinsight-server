<?php

namespace App\Domain\Xhprof;

interface XhprofRepository
{

    /**
     * @param array $filterParams
     * @return array
     */
    public function searchByCriteria(array $filterParams = []): array;


    /**
     * @param string $id
     * @return mixed
     */
    public function findXhprofOfId(string $id);
}