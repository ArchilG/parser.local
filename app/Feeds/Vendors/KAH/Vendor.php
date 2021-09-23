<?php

namespace App\Feeds\Vendors\KAH;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Utils\Link;

class Vendor extends HttpProcessor
{
    public const PRODUCT_LINK_CSS_SELECTORS = [ 'a.single-product-link' ];

    protected array $first = [ 'https://www.kalmarhome.com/products' ];

    public function isValidFeedItem( FeedItem $fi ): bool
    {
        return count( $fi->getImages() ) && $fi->getCostToUs() > 0 && !empty( $fi->getMpn() );
    }
}