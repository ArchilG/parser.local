<?php

namespace App\Feeds\Vendors\GLX;
use App\Feeds\Utils\Link;
use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;

class Vendor extends SitemapHttpProcessor
{
    public array $first = [ 'https://www.galaxygold.com/sitemap.xml' ];
//
//    public function filterProductLinks( Link $link ): bool
//    {
//        return str_contains( $link->getUrl(), '/Gold-Chandelier-Diamond-Earrings-p/1528-w.htm' );
//    }

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        return $fi->getCostToUs() > 0 && count($fi->getImages());
    }

}
