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
        return str_contains( $link->getUrl(), '/product-page/3-flavor-variety-pack-digestion-skin-coat-hip-joint-bone-broth-bone-broth' );
    }

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        if ( $fi->isGroup() ) {
            $fi->setChildProducts( array_values(
                array_filter( $fi->getChildProducts(), static fn( FeedItem $item ) => !empty( $item->getMpn() ) && count( $item->getImages() ) )
            ) );
            return count( $fi->getChildProducts() );
        }
        return !empty( $fi->getMpn() ) && count( $fi->getImages() );
    }
}
