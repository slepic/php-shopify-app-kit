<?php

declare(strict_types=1);

namespace Slepic\Shopify\Webhooks;

use Slepic\Shopify\Credentials\ApiSecretKey;
use Slepic\Shopify\Credentials\ShopDomain;
use Psr\Http\Message\RequestInterface;

class WebhookRequestFactory
{
    private ApiSecretKey $secret;

    public function __construct(ApiSecretKey $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param RequestInterface $request
     * @return WebhookRequest
     * @throws InvalidWebhookRequestException
     */
    public function createWebhookRequest(RequestInterface $request): WebhookRequest
    {
        $shopDomainHeader = $request->getHeaderLine('X-Shopify-Shop-Domain');
        try {
            $shopDomain = ShopDomain::create($shopDomainHeader);
        } catch (\Throwable $e) {
            throw new InvalidWebhookRequestException(
                "Invalid shop domain: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        $topicHeader = $request->getHeaderLine('X-Shopify-Topic');
        if (!$topicHeader) {
            throw new InvalidWebhookRequestException('Missing webhook topic');
        }

        $hmacHeader = $request->getHeaderLine('X-Shopify-Hmac-Sha256');
        if (!$hmacHeader) {
            throw new InvalidWebhookRequestException('Missing webhook signature');
        }

        $contentTypeHeader = $request->getHeaderLine('Content-Type');
        if (!$contentTypeHeader) {
            throw new InvalidWebhookRequestException('Missing webhook content type');
        }

        $requestBody = (string) $request->getBody();
        $hmacString = base64_encode(hash_hmac('sha256', $requestBody, (string) $this->secret, true));

        if (!\hash_equals($hmacHeader, $hmacString)) {
            throw new InvalidWebhookRequestException(\sprintf(
                "The HMAC provided by Shopify (%s) doesn't match the HMAC verification (%s).",
                $hmacHeader,
                $hmacString
            ));
        }

        if (\strpos($contentTypeHeader, 'application/json') !== false) {
            $data = \json_decode($requestBody, true);
            if (!\is_array($data)) {
                throw new InvalidWebhookRequestException('Failed to decode webhook body');
            }
        } else {
            throw new InvalidWebhookRequestException('Unsupported webhook content type ' . $contentTypeHeader);
        }

        return new WebhookRequest($shopDomain, $topicHeader, $data);
    }
}
