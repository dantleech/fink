<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Status;

interface Limiter
{
    public function limitReached(Status $status): bool;
}
