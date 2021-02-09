<?php

declare(strict_types=1);

namespace Slepic\Shopify\Client;

use Slepic\Shopify\Credentials\ShopDomain;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientExceptionInterface;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientInterface;

final class ShopifyGraphqlClient implements ShopifyGraphqlClientInterface
{
    private ApiClientInterface $client;
    private ShopDomain $shopDomain;
    private array $headers;

    public function __construct(ApiClientInterface $client, ShopDomain $shopDomain, array $headers)
    {
        $this->client = $client;
        $this->shopDomain = $shopDomain;
        $this->headers = $headers;
    }

    public function query(string $query, array $variables = []): ShopifyResponse
    {
        try {
            $response = $this->client->call(
                $this->shopDomain->getShopUrl(),
                'POST',
                '/admin/api/graphql.json',
                [],
                $this->headers,
                [
                    'query' => $query,
                    'variables' => (object) $variables,
                ]
            );
        } catch (ApiClientExceptionInterface $e) {
            throw new ShopifyClientException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $responseBody = $response->getParsedBody() ?? [];

        if ($responseBody['errors'] ?? false) {
            throw new ShopifyClientException(\sprintf(
                'Shopify GraphQL request failed with errors: %s',
                \json_encode($responseBody['errors'])
            ));
        }

        if (!isset($responseBody['data'])) {
            throw new ShopifyClientException(
                'Shopify GraphQL client failed to recognize response data structure - missing data property'
            );
        }

        $responseData = $responseBody['data'];

        if (
            isset($responseBody['extensions']['cost']['throttleStatus']['maximumAvailable']) &&
            isset($responseBody['extensions']['cost']['throttleStatus']['currentlyAvailable']) &&
            isset($responseBody['extensions']['cost']['actualQueryCost'])
        ) {
            $callsLimit = (int) $responseBody['extensions']['cost']['throttleStatus']['maximumAvailable'];
            $callsRemaining = (int) $responseBody['extensions']['cost']['throttleStatus']['currentlyAvailable'];
            $callsMade = $callsLimit - $callsRemaining;
            $cost = (int) $responseBody['extensions']['cost']['actualQueryCost'];

            return ShopifyResponse::limited(
                $response->getStatusCode(),
                $response->getRawBody(),
                $responseData,
                $callsMade,
                $callsLimit,
                $cost,
            );
        }

        return ShopifyResponse::unlimited($response->getStatusCode(), $response->getRawBody(), $responseData);
    }
}
