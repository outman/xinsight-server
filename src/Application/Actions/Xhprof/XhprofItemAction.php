<?php

namespace App\Application\Actions\Xhprof;

use App\Application\Actions\Action;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class XhprofItemAction extends XhprofAction
{

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        try {
            $id = $this->getParam('id');
            $data = $this->xhprofRepository->findXhprofOfId($id);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $data = (object) [];
        }
        return $this->respondWithData($data);
    }
}