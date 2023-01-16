<?php

namespace App\Domain\Xhprof;

use App\Domain\DomainException\DomainRecordNotFoundException;

class XhprofNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}