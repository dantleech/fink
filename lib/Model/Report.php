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

    /**
     * @var ReferringElement
     */
    private $referringElement;

    public function __construct(
        Url $url,
        HttpStatusCode $statusCode = null,
        Exception $exception = null,
        int $requestTime = 0,
        ReferringElement $referringElement = null
    ) {
        $this->url = $url;
        $this->statusCode = $statusCode;
        $this->requestTime = $requestTime;
        $this->exception = $exception;
        $this->referringElement = $referringElement ?: ReferringElement::none();
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
            'distance' => $this->url->distance(),
            'exception' => $this->exception ? $this->exception->getMessage() : null,
            'referrer' => $referrer ? $referrer->__toString() : null,
            'referrer_title' => $this->referringElement->title(),
            'referrer_xpath' => $this->referringElement->path(),
            'request_time' => $this->requestTime,
            'status' => $this->statusCode ? $this->statusCode->toInt() : null,
            'url' => $this->url->__toString(),
        ];
    }

    public function requestTime(): int
    {
        return $this->requestTime;
    }
}
