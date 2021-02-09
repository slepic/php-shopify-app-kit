<?php

declare(strict_types=1);

namespace Slepic\Shopify\Client;

interface ShopifyClientInterface
{
    /**
     * @param string $method
     * @param string $endpoint
     * @param array|object|null $body
     * @param array<string, mixed> $query
     * @return ShopifyResponse
     * @throws ShopifyClientException
     */
    public function call(string $method, string $endpoint, $body = null, array $query = []): ShopifyResponse;
}
