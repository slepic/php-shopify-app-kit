<?php

declare(strict_types=1);

namespace Slepic\Shopify\Credentials;

use Slepic\Shopify\ShopifyExceptionInterface;

class ShopDomainException extends \InvalidArgumentException implements ShopifyExceptionInterface
{

}
