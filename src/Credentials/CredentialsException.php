<?php

declare(strict_types=1);

namespace Slepic\Shopify\Credentials;

use Slepic\Shopify\ShopifyExceptionInterface;

class CredentialsException extends \InvalidArgumentException implements ShopifyExceptionInterface
{

}
