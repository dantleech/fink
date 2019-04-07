<?php

namespace DTL\Extension\Fink\Model;

use DateTimeImmutable;
use Exception;

class ReportBuilder
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var HttpStatusCode
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

    /**
     * @var ReferringElement|null
     */
    private $referringElement;

    /**
     * @var DateTimeImmutable
     */
    private $timestamp;

    private function __construct(Url $url)
    {
        $this->url = $url;
        $this->timestamp = new DateTimeImmutable();
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

    public function withReferringElement(ReferringElement $referringElement = null): self
    {
        $this->referringElement = $referringElement;
        return $this;
    }

    public function withTimestamp(DateTimeImmutable $dateTimeImmutable)
    {
        $this->timestamp = $dateTimeImmutable;
        return $this;
    }

    public function build(): Report
    {
        return new Report(
            $this->url,
            $this->statusCode,
            $this->exception,
            $this->requestTime,
            $this->referringElement,
            $this->timestamp
        );
    }
}
