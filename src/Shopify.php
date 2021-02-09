<?php

declare(strict_types=1);

namespace Slepic\Shopify;

use Slepic\Shopify\Client\ShopifyClient;
use Slepic\Shopify\Client\ShopifyClientInterface;
use Slepic\Shopify\Client\ShopifyGraphqlClient;
use Slepic\Shopify\Client\ShopifyGraphqlClientInterface;
use Slepic\Shopify\Credentials\AccessToken;
use Slepic\Shopify\Credentials\ApiCredentials;
use Slepic\Shopify\Credentials\ApiKey;
use Slepic\Shopify\Credentials\ShopDomain;
use Slepic\Shopify\OAuth\AuthorizationException;
use Slepic\Shopify\OAuth\AuthorizationRequest;
use Slepic\Shopify\OAuth\AuthorizationResponse;
use Slepic\Shopify\OAuth\Scopes;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientExceptionInterface;
use Slexphp\Http\SimpleApiClient\Contracts\ApiClientInterface;

class Shopify
{
    private ApiClientInterface $client;
    private ApiCredentials $credentials;

    public function __construct(ApiClientInterface $client, ApiCredentials $credentials)
    {
        $this->client = $client;
        $this->credentials = $credentials;
    }

    public static function create(ApiClientInterface $client, ApiCredentials $credentials): self
    {
        return new self($client, $credentials);
    }

    public static function privateAppClient(
        ApiClientInterface $client,
        ShopDomain $shopDomain,
        ApiCredentials $credentials
    ): ShopifyClientInterface {
        $headers = self::privateAppAuthHeaders($credentials);
        return new ShopifyClient($client, $shopDomain, $headers);
    }

    public static function publicAppClient(
        ApiClientInterface $client,
        ShopDomain $shopDomain,
        AccessToken $accessToken
    ): ShopifyClientInterface {
        $headers = self::publicAppAuthHeaders($accessToken);
        return new ShopifyClient($client, $shopDomain, $headers);
    }

    public static function publicApp(
        ApiClientInterface $client,
        ApiCredentials $credentials,
        string $redirectUrl,
        Scopes $requiredScopes,
        ?Scopes $optionalScopes = null
    ): ShopifyPublicApp {
        return self::create($client, $credentials)
            ->createPublicApp($redirectUrl, $requiredScopes, $optionalScopes);
    }

    public function getApiKey(): ApiKey
    {
        return $this->credentials->getApiKey();
    }

    public function validateShopRequest(array $requestData): ShopDomain
    {
        return ShopDomain::create($requestData['shop'] ?? null);
    }

    public function getAuthorizationUrl(
        ShopDomain $shopDomain,
        Scopes $scopes,
        string $redirectUrl,
        string $nonce = '',
        bool $onlineAccessMode = false
    ): string {
        $args = [
            'client_id'    => (string) $this->credentials->getApiKey(),
            'scope'        => (string) $scopes,
            'redirect_uri' => $redirectUrl,
            'state'        => $nonce,
        ];

        if ($onlineAccessMode) {
            $args['grant_options[]'] = 'per-user';
        }

        return $shopDomain->getShopUrl() . '/admin/oauth/authorize?' . http_build_query($args);
    }

    /**
     * @param array $requestData
     * @return ShopDomain
     * @throws AuthorizationException
     */
    public function validateSecuredRequest(array $requestData): ShopDomain
    {
        try {
            $requestShopDomain = $this->validateShopRequest($requestData);
        } catch (\Throwable $e) {
            throw new AuthorizationException("The shop provided by Shopify is invalid: " . $e->getMessage());
        }

        $requiredKeys = ['hmac'];
        foreach ($requiredKeys as $required) {
            if (!in_array($required, array_keys($requestData))) {
                throw new AuthorizationException(
                    "The provided request data is missing one of the following keys: " . implode(', ', $requiredKeys)
                );
            }
        }

        // Check HMAC signature. See https://help.shopify.com/api/getting-started/authentication/oauth#verification
        $hmacSource = [];
        foreach ($requestData as $key => $value) {
            if ($key === 'hmac') {
                continue;
            }

            // Replace the characters as specified by Shopify in the keys and values
            $valuePatterns = [
                '&' => '%26',
                '%' => '%25',
            ];
            $keyPatterns = array_merge($valuePatterns, ['=' => '%3D']);
            $key = str_replace(array_keys($keyPatterns), array_values($keyPatterns), $key);
            $value = str_replace(array_keys($valuePatterns), array_values($valuePatterns), $value);

            $hmacSource[] = $key . '=' . $value;
        }

        // Sort the key value pairs lexographically and then generate the HMAC signature of the provided data
        sort($hmacSource);
        $hmacBase = implode('&', $hmacSource);
        $hmacString = hash_hmac('sha256', $hmacBase, (string) $this->credentials->getSecret());

        // Verify that the signatures match
        if ($hmacString !== $requestData['hmac']) {
            throw new AuthorizationException(\sprintf(
                "The HMAC provided by Shopify (%s) doesn't match the HMAC verification (%s).",
                $requestData['hmac'],
                $hmacString
            ));
        }

        return $requestShopDomain;
    }

    /**
     * @param array $requestData
     * @param string $nonce
     * @param ShopDomain|null $shopDomain
     * @return AuthorizationRequest
     * @throws AuthorizationException
     */
    public function validateAuthorizationRequest(
        array $requestData,
        string $nonce = '',
        ?ShopDomain $shopDomain = null
    ): AuthorizationRequest {
        if (!isset($requestData['code']) || !\is_string($requestData['code']) || empty($requestData['code'])) {
            throw new AuthorizationException("Invalid or missing grant code.");
        }

        if (($requestData['state'] ?? null)  !== $nonce) {
            throw new AuthorizationException("Invalid or missing nonce.");
        }

        $requestShopDomain = $this->validateSecuredRequest($requestData);

        if ($shopDomain !== null && !$shopDomain->equals($requestShopDomain)) {
            throw new AuthorizationException(\sprintf(
                "The shop provided by Shopify (%s) does not match the shop provided to this API (%s)",
                (string) $requestShopDomain,
                (string) $shopDomain
            ));
        }

        return new AuthorizationRequest($requestShopDomain, $requestData['code']);
    }

    /**
     * @param AuthorizationRequest $request
     * @return AuthorizationResponse
     * @throws AuthorizationException
     */
    public function authorizeApplication(AuthorizationRequest $request): AuthorizationResponse
    {
        try {
            $response = $this->client->call(
                $request->getShopDomain()->getShopUrl(),
                'POST',
                '/admin/oauth/access_token',
                [],
                [],
                [
                    'client_id'     => (string) $this->credentials->getApiKey(),
                    'client_secret' => (string) $this->credentials->getSecret(),
                    'code'          => $request->getCode(),
                ]
            );
        } catch (ApiClientExceptionInterface $e) {
            throw new AuthorizationException(
                'Authorization request failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return AuthorizationResponse::fromArray($response->getParsedBody());
    }

    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface
    {
        $headers = self::publicAppAuthHeaders($accessToken);
        return new ShopifyClient($this->client, $shopDomain, $headers);
    }

    public function createPrivateAppClient(ShopDomain $shopDomain): ShopifyClientInterface
    {
        $headers = self::privateAppAuthHeaders($this->credentials);
        return new ShopifyClient($this->client, $shopDomain, $headers);
    }

    public function createPublicApp(
        string $redirectUrl,
        Scopes $requiredScopes,
        ?Scopes $optionalScopes = null
    ): ShopifyPublicApp {
        return new ShopifyPublicApp($this, $redirectUrl, $requiredScopes, $optionalScopes);
    }

    public function createPublicAppGraphqlClient(
        ShopDomain $shopDomain,
        AccessToken $accessToken
    ): ShopifyGraphqlClientInterface {
        $headers = self::publicAppAuthHeaders($accessToken);
        return new ShopifyGraphqlClient($this->client, $shopDomain, $headers);
    }

    private static function publicAppAuthHeaders(AccessToken $accessToken): array
    {
        return ['X-Shopify-Access-Token' => (string) $accessToken];
    }

    private static function privateAppAuthHeaders(ApiCredentials $credentials): array
    {
        $authHeader = 'Basic ' . \base64_encode($credentials->getApiKey() . ':' . $credentials->getSecret());
        return ['Authorization' => $authHeader];
    }
}
