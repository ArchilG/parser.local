<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Link;

class Vendor extends SitemapHttpProcessor
{
    public array $first = [ 'https://www.galaxygold.com/sitemap.xml' ];

    protected const DELAY_S = 0.5;
    protected const REQUEST_TIMEOUT_S = 60;

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        return $fi->getCostToUs() > 0;
    }

}
