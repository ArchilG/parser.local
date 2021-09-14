<?php

namespace App\Feeds\Vendors\NDP;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Link;

class Vendor extends SitemapHttpProcessor
{
    public array $first = [ 'https://www.naturesdietpet.com/store-products-sitemap.xml' ];

    protected const DELAY_S = 0.5;
    protected const REQUEST_TIMEOUT_S = 60;

    public function filterProductLinks( Link $link ): bool
    {
        return str_contains( $link->getUrl(), '/product-page/' );
    }

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        return count( $fi->getImages() ) && $fi->getCostToUs() > 0;
    }

}
