<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\ParserCrawler;

class Vendor extends SitemapHttpProcessor
{
    public array $first = [ 'https://www.galaxygold.com/sitemap.xml' ];

    protected const DELAY_S = 0.5;
    protected const REQUEST_TIMEOUT_S = 60;

    public function parseContent(Data $data, array $params = []): array
    {
        $this->node = new ParserCrawler( $data->getData(), $params[ 'url' ] ?? '' );
        $this->uri = $params[ 'url' ] ?? '';
        $this->getMeta();

        $item = new FeedItem( $this );
        if($this->exists( '[itemtype="http://schema.org/Product"]' )) {
            $mpn = $item->isGroup() ? md5( microtime() . mt_rand() ) : $item->mpn;

            return [ $mpn => $item ];
        } else {
            return [];
        }
    }
//    public function filterProductLinks( Link $link ): bool
//    {
//        return str_contains( $link->getUrl(), '/Gold-Necklace-Bar-Diamonds-p/2120.htm' );
//    }

    protected function isValidFeedItem( FeedItem $fi ): bool
    {
        return $fi->getCostToUs() > 0;
    }

}
