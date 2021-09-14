<?php

namespace App\Feeds\Vendors\NDP;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Parser\HtmlParser;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;
use Symfony\Component\DomCrawler\Crawler;

class Parser extends HtmlParser
{
    private array $short_product_info = [];

    public function getProduct(): string
    {
        return $this->getText('._2qrJF');
    }

    public function getImages(): array
    {
        return $this->getSrcImages('._2tkQd img');
    }

    public function getDescription(): string
    {
        $description = $this->getHtml( '[data-hook="description"]' );
        return FeedHelper::cleanProductDescription($description);
    }

    public function getMpn(): string
    {
        $sku = $this->getText( '._1rwRc' );
        return StringHelper::removeSpaces(str_replace('SKU: ','',$sku));
    }

    public function getCostToUs(): float
    {
        return $this->getMoney('[data-hook="formatted-primary-price"]');
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }
}
