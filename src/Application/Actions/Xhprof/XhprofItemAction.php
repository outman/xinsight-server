<?php

namespace App\Application\Actions\Xhprof;

use App\Application\Actions\Action;
use App\Application\Library\ProfileDataParser;
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

            if ($data && $data['profile']) {
                $profile = json_decode($data['profile'], true);
                $parser = new ProfileDataParser(empty($profile['profile']) ? [] : $profile['profile']);
                $data['profile'] = [
                    'wt' => $parser->getFlamegraph('wt', 0)['data'],
                    'mu' => $parser->getFlamegraph('mu', 0)['data'],
                ];
            } else {
                $data['profile'] = [
                    'wt' => [],
                    'mu' => [],
                ];
            }

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $data = (object) [];
        }
        return $this->respondWithData($data);
    }
}
