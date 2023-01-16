<?php

namespace App\Application\Actions\Xhprof;

use App\Application\Actions\Action;
use App\Domain\Xhprof\XhprofRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class XhprofAction extends Action
{
    /**
     * @var XhprofRepository
     */
    protected XhprofRepository $xhprofRepository;

    /**
     * @param LoggerInterface $logger
     * @param ContainerInterface $container
     * @param XhprofRepository $xhprofRepository
     */
    public function __construct(LoggerInterface $logger, ContainerInterface $container, XhprofRepository $xhprofRepository)
    {
        parent::__construct($logger, $container);
        $this->xhprofRepository = $xhprofRepository;
    }
}