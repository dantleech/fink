<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;

class Sampler
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new DefaultClient();
    }

    public function sample(string $url)
    {
        $response = yield $this->client->request($url);
        assert($response instanceof Response);
        $response->getStatus();

    }
}
