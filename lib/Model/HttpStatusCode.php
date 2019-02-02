<?php

namespace DTL\Extension\Fink\Model;

class HttpStatusCode
{
    /**
     * @var int
     */
    private $statusCode;

    private function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public static function fromInt(int $statusCode): self
    {
        return new self($statusCode);
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    public function toInt(): int
    {
        return $this->statusCode;
    }

    public function toString(): string
    {
        return (string) $this->statusCode;
    }
}
