<?php

namespace App\Feeds\Vendors\NDP;

use App\Feeds\Parser\HtmlParser;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $short_product_info = [];

    public function getProduct(): string
    {
        return $this->getText('[data-hook="product-title"]');
    }

    public function getImages(): array
    {
        $images = array_filter($this->getSrcImages('[data-hook="thumbnail-image"]'));

        if (!$images) {
            $images = array_filter([$this->getAttr('.media-wrapper-hook', 'href')]);
        }

        foreach ($images as &$image) {
            preg_match('~(.*?com.*?\.\w+)/~', $image, $matches);
            $image = $matches[1];
        }

        return $images ?: $this->getSrcImages('[data-hook="product-image-item"]');
    }

    public function getDescription(): string
    {
        return $this->getHtml('[data-hook="description"]');
    }

    public function getMpn(): string
    {
        $sku = $this->getText('[data-hook="sku"]');
        return StringHelper::removeSpaces(str_replace('SKU: ', '', $sku));
    }

    public function getCostToUs(): float
    {
        return $this->getMoney('[data-hook="formatted-primary-price"]');
    }

    public function getAvail(): ?int
    {
        return $this->getAttr('[property="og:availability"]', 'content') === 'InStock' ? self::DEFAULT_AVAIL_NUMBER : 0;
    }
}
