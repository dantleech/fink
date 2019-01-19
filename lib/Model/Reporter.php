<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\Response;

interface Reporter
{
    public function logResponse(Response $response): void;
}
