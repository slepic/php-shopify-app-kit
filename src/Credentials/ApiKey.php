<?php

declare(strict_types=1);

namespace Slepic\Shopify\Credentials;

final class ApiKey extends Credential
{
    protected const NOT_STRING_ERROR = 'API key must be a string.';
    protected const EMPTY_TOKEN_ERROR = 'Empty API key.';
}
