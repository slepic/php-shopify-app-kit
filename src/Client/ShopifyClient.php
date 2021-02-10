<?php

declare(strict_types=1);

namespace Slepic\Shopify\Client;

use Slepic\Shopify\Credentials\ShopDomain;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientExceptionInterface;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientInterface;

final class ShopifyClient implements ShopifyClientInterface
{
    private ApiClientInterface $client;
    private ShopDomain $shopDomain;
    private array $headers;

    public function __construct(ApiClientInterface $client, ShopDomain $shopDomain, array $headers)
    {
        $this->client = $client;
        $this->shopDomain = $shopDomain;
        $this->headers = $headers;
        $this->headers['content-type'] = 'application/json';
    }

    public function call(string $method, string $endpoint, $body = null, array $query = []): ShopifyResponse
    {
        try {
            $response = $this->client->call(
                $this->shopDomain->getShopUrl(),
                $method,
                $endpoint,
                $query,
                $this->headers,
                $body
            );
        } catch (ApiClientExceptionInterface $e) {
            throw new ShopifyClientException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $matches = [];
        $apiCallLimitHeader = $response->getHeaderLine('X-Shopify-Shop-Api-Call-Limit');
        if (!$apiCallLimitHeader || !\preg_match('#^(\\d+)/(\\d+)$#', $apiCallLimitHeader, $matches)) {
            return ShopifyResponse::unlimited(
                $response->getStatusCode(),
                $response->getRawBody(),
                $response->getParsedBody() ?? []
            );
        }
        return ShopifyResponse::limited(
            $response->getStatusCode(),
            $response->getRawBody(),
            $response->getParsedBody() ?? [],
            (int) $matches[0],
            (int) $matches[1],
            1
        );
    }
}
