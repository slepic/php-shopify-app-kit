<?php

declare(strict_types=1);

namespace Slepic\Shopify\Client;

interface ShopifyGraphqlClientInterface
{
    /**
     * @param string $query
     * @param array $variables
     * @return ShopifyResponse
     * @throws ShopifyClientException
     */
    public function query(string $query, array $variables = []): ShopifyResponse;
}
