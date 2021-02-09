<?php

declare(strict_types=1);

namespace Slepic\Shopify\OAuth;

use Slepic\Shopify\ShopifyExceptionInterface;

class ScopeException extends \InvalidArgumentException implements ShopifyExceptionInterface
{

}
