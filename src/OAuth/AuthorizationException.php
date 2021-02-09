<?php

declare(strict_types=1);

namespace Slepic\Shopify\OAuth;

use Slepic\Shopify\ShopifyExceptionInterface;

class AuthorizationException extends \RuntimeException implements ShopifyExceptionInterface
{

}
