<?php

declare(strict_types=1);

namespace Slepic\Shopify\Client;

final class ShopifyResponse
{
    private int $status;
    private string $rawBody;
    private array $parsedBody;
    private ?int $callsMade;
    private ?int $callLimit;
    private ?int $cost;

    private function __construct(
        int $status,
        string $rawBody,
        array $parsedBody,
        ?int $callsMade = null,
        ?int $callLimit = null,
        ?int $cost = null
    ) {
        $this->status = $status;
        $this->rawBody = $rawBody;
        $this->parsedBody = $parsedBody;
        $this->callsMade = $callsMade;
        $this->callLimit = $callLimit;
        $this->cost = $cost;
    }

    public static function unlimited(int $status, string $rawBody, array $parsedBody): self
    {
        return new self($status, $rawBody, $parsedBody);
    }

    public static function limited(
        int $status,
        string $rawBody,
        array $parsedBody,
        int $callsMade,
        int $callLimit,
        int $cost
    ): self {
        return new self($status, $rawBody, $parsedBody, $callsMade, $callLimit, $cost);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getCallsMade(): ?int
    {
        return $this->callsMade;
    }

    public function getCallLimit(): ?int
    {
        return $this->callLimit;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }
}
