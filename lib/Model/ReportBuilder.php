<?php

namespace DTL\Extension\Fink\Model;

use Exception;

class ReportBuilder
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var Url
     */
    private $referringUrl;

    /**
     * @var Exception
     */
    private $exception;

    /**
     * @var int
     */
    private $requestTime = 0;

    private function __construct(Url $url)
    {
        $this->url = $url;
    }

    public static function forUrl(Url $url): self
    {
        return new self($url);
    }

    public function withStatus(int $statusCode): self
    {
        $this->statusCode = HttpStatusCode::fromInt($statusCode);
        return $this;
    }

    public function withException(Exception $exception): self
    {
        $this->exception = $exception;
        return $this;
    }

    public function withReferringUrl(Url $url): self
    {
        $this->referringUrl = $url;
        return $this;
    }

    public function withRequestTime(int $microseconds): self
    {
        $this->requestTime = $microseconds;
        return $this;
    }

    public function build(): Report
    {
        return new Report(
            $this->url,
            $this->statusCode,
            $this->exception,
            $this->requestTime
        );
    }
}
