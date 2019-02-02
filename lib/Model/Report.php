<?php

namespace DTL\Extension\Fink\Model;

use Exception;

class Report
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var ?HttpStatusCode
     */
    private $statusCode;

    /**
     * @var int
     */
    private $requestTime;

    /**
     * @var ?Exception
     */
    private $exception;

    public function __construct(
        Url $url,
        HttpStatusCode $statusCode = null,
        Exception $exception = null,
        int $requestTime = 0
    ) {
        $this->url = $url;
        $this->statusCode = $statusCode;
        $this->requestTime = $requestTime;
        $this->exception = $exception;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function statusCode(): ?HttpStatusCode
    {
        return $this->statusCode;
    }

    public function isSuccess(): bool
    {
        if ($this->statusCode) {
            return $this->statusCode->isSuccess();
        }

        return false;
    }

    public function toArray(): array
    {
        $referrer = $this->url->referrer();

        return [
            'url' => $this->url->__toString(),
            'distance' => $this->url->distance(),
            'referrer' => $referrer ? $referrer->__toString() : null,
            'status' => $this->statusCode ? $this->statusCode->toInt() : null,
            'request-time' => $this->requestTime,
            'exception' => $this->exception ? $this->exception->getMessage() : null,
        ];
    }

    public function requestTime(): int
    {
        return $this->requestTime;
    }
}
