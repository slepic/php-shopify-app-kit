<?php

declare(strict_types=1);

namespace Slepic\Shopify\OAuth;

use Slepic\Shopify\Credentials\ShopDomain;

final class AuthorizationRequest
{
    private ShopDomain $shopDomain;
    private string $code;

    public function __construct(ShopDomain $shopDomain, string $code)
    {
        $this->shopDomain = $shopDomain;
        $this->code = $code;
    }

    public function getShopDomain(): ShopDomain
    {
        return $this->shopDomain;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
