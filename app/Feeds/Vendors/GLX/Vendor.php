<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;

class Vendor extends SitemapHttpProcessor
{
    public array $first = [ 'https://www.galaxygold.com/sitemap.xml' ];

    protected const DELAY_S = 0.5;
    protected const REQUEST_TIMEOUT_S = 60;

//    public function filterProductLinks( Link $link ): bool
//    {
//        return str_contains( $link->getUrl(), '/Gold-Necklace-Bar-Purple-Amethysts-p/5750y.htm' );
//    }

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        return $fi->getCostToUs() > 0 && count($fi->getImages());
    }

}
