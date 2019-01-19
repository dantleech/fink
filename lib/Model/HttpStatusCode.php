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

    public static function fromInt(int $statusCode)
    {
        return new self($statusCode);
    }

    public function isSuccess(): bool
    {
        return in_array($this->statusCode, [200]);
    }

    public function toInt(): int
    {
        return $this->statusCode;
    }
}
