<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [
        'x' => null,
        'y' => null,
    ];

    public function beforeParse(): void
    {
        $offerText = $this->getText('[itemprop="offers"]');
        $matches = [];
        preg_match( '/Width:.+?\((.*?)mm\)/is', $offerText, $matches );
        if ($matches) $this->dims['x'] = trim($matches[1]);

        $matches = [];
        preg_match( '/Height:.+?\((.*?)mm\)/is', $offerText, $matches );
        if ($matches) $this->dims['y'] = trim($matches[1]);

    }
    public function getProduct(): string
    {
        return $this->getText('span[itemprop="name"]');
    }

    public function getImages(): array
    {
        return array_values( array_unique( $this->getLinks( '#altviews a' ) ) );
    }

    public function getDescription(): string
    {
        return $this->getHtml('#product_description');
    }

    public function getShortDescription(): array
    {
        return $this->getContent( '#viewitem-table .viewitemtable-data' );
    }

    public function getMpn(): string
    {
        $sku = $this->getText('.product_code');
        return StringHelper::removeSpaces(str_replace('SKU: ', '', $sku));
    }

    public function getCostToUs(): float
    {
        return $this->getMoney('[itemprop="price"]');
    }

    public function getListPrice(): ?float
    {
        return $this->getMoney('.list-price');
    }

    public function getAvail(): ?int
    {
        return $this->getAttr('[itemprop="availability"]', 'content') === 'InStock' ? self::DEFAULT_AVAIL_NUMBER : 0;
    }
    public function getCategories(): array
    {
        $categories = $this->getContent('.vCSS_breadcrumb_td a');
        array_shift($categories);
        return array_filter($categories);
    }
    public function getDimX(): ?float
    {
        return $this->dims[ 'x' ];
    }

    public function getDimY(): ?float
    {
        return $this->dims[ 'y' ];
    }

    public function isGroup(): bool
    {
        return $this->getAttr( 'form.variations_form', 'data-product_variations' );
    }
}
