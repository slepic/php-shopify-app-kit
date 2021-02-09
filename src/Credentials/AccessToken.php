<?php

declare(strict_types=1);

namespace Slepic\Shopify\Credentials;

final class AccessToken extends Credential
{
    protected const NOT_STRING_ERROR = 'Access token must be a string.';
    protected const EMPTY_TOKEN_ERROR = 'Empty access token.';
}
