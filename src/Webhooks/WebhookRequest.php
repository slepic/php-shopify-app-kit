<?php

declare(strict_types=1);

namespace Slepic\Shopify\Webhooks;

use Slepic\Shopify\Credentials\ShopDomain;

final class WebhookRequest
{
    private string $webhookId;
    private string $apiVersion;
    private ShopDomain $shopDomain;
    private string $topic;
    private array $data;

    public function __construct(
        string $webhookId,
        string $apiVersion,
        ShopDomain $shopDomain,
        string $topic,
        array $data
    ) {
        $this->webhookId = $webhookId;
        $this->apiVersion = $apiVersion;
        $this->shopDomain = $shopDomain;
        $this->topic = $topic;
        $this->data = $data;
    }

    public function getWebhookId(): string
    {
        return $this->webhookId;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
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
