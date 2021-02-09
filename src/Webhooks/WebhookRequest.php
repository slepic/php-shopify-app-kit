<?php

declare(strict_types=1);

namespace Slepic\Shopify\Webhooks;

use Slepic\Shopify\Credentials\ShopDomain;

final class WebhookRequest
{
    private ShopDomain $shopDomain;
    private string $topic;
    private array $data;

    public function __construct(ShopDomain $shopDomain, string $topic, array $data)
    {
        $this->shopDomain = $shopDomain;
        $this->topic = $topic;
        $this->data = $data;
    }

    public function getShopDomain(): ShopDomain
    {
        return $this->shopDomain;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
