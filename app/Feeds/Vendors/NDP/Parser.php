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
        return $this->getText('[data-hook="product-title"]');
    }

    public function getImages(): array
    {
        $images =  array_filter($this->getSrcImages( '[data-hook="thumbnail-image"]'));

        if (!$images) {
            $images = array_filter([$this->getAttr( '.media-wrapper-hook', 'href' )]);
        }

        if($images) {
            foreach ($images as &$image) {
                $strPos=strpos($image, ".png");
                if ($strPos) {
                    $image=substr($image, 0, $strPos + 4);
                }
                $strPos=strpos($image, ".jpg");
                if ($strPos) {
                    $image=substr($image, 0, $strPos + 4);
                }
            }

            return $images;
        }

        return $this->getSrcImages('[data-hook="product-image-item"]');
    }

    public function getDescription(): string
    {
        return $this->getHtml( '[data-hook="description"]' );
    }

    public function getMpn(): string
    {
        $sku = $this->getText( '[data-hook="sku"]' );
        return StringHelper::removeSpaces(str_replace('SKU: ','',$sku));
    }

    public function getCostToUs(): float
    {
        return $this->getMoney('[data-hook="formatted-primary-price"]');
    }

    public function getAvail(): ?int
    {
        if($this->getAttr('[property="og:availability"]','content') === 'InStock') {
            return self::DEFAULT_AVAIL_NUMBER;
        }
        return 0;
    }
}
