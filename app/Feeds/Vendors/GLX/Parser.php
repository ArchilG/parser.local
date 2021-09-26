<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Parser\HtmlParser;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [
        'x' => null,
        'y' => null,
    ];

    private array $images = [];
    private string $name = '';
    private string $description = '';
    private array $shortDescription = [];
    private string $sku = '';
    private float $price = 0;
    private float $listPrice = 0;
    private int $available = 0;
    private array $categories = [];

    public function beforeParse(): void
    {
        $offerText = $this->getText('[itemprop="offers"]');
        $matches = [];
        preg_match( '/Width:.+?\((.*?)mm\)/is', $offerText, $matches );
        if (!empty($matches[1])) $this->dims['x'] = trim($matches[1]);

        $matches = [];
        preg_match( '/Height:.+?\((.*?)mm\)/is', $offerText, $matches );
        if (!empty($matches[1])) $this->dims['y'] = trim($matches[1]);

        $matches = [];
        preg_match_all( "/preloadImage\('.*?,'\/\/(.*?)\?v/", $this->node->html(), $matches );
        if (!empty($matches[1])) {
            $this->images = array_map( static function ( $item ) {
                return "https://$item";
            }, $matches[ 1 ] );
        }

    }
    public function getProduct(): string
    {
        $this->name = $this->getText('span[itemprop="name"]');
        return  $this->name;
    }

    public function getImages(): array
    {
        if (!empty($this->images)) return $this->images;
        $this->images = array_values(array_unique($this->getLinks('#altviews a')));
        return $this->images;
    }

    public function getDescription(): string
    {
        $this->description = $this->getHtml('#product_description');
        return  $this->description;
    }

    public function getShortDescription(): array
    {
        $this->shortDescription = $this->getContent( '#viewitem-table .viewitemtable-data' );
        return $this->shortDescription;
    }

    public function getMpn(): string
    {
        $this->sku = StringHelper::removeSpaces(str_replace('SKU: ', '', $this->getText('.product_code')));
        return $this->sku;
    }

    public function getCostToUs(): float
    {
        $this->price = $this->getMoney('[itemprop="price"]');
        return $this->price;
    }

    public function getListPrice(): ?float
    {
        $this->listPrice = $this->getMoney('.list-price');
        return $this->listPrice;
    }

    public function getAvail(): ?int
    {
        $this->available = $this->getAttr('[itemprop="availability"]', 'content') === 'InStock' ? self::DEFAULT_AVAIL_NUMBER : 0;
        return $this->available;
    }
    public function getCategories(): array
    {
        $this->categories = $this->getContent('.vCSS_breadcrumb_td a');
        array_shift($this->categories);
        return array_filter($this->categories);
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
        return $this->exists( '#options_table select[title="Gold Color"]' );
    }
    public function getChildProducts( FeedItem $parentFi ): array
    {
        $child = [];

        $i = 0;
        $colors = $this->getContent( '#options_table select[title="Gold Color"] option' );
        $ropeChains = $this->getContent( '#options_table select[title="Rope Chain Option"] option' );

        foreach ( $colors as $color ) {

            $colorPrice = 0;
            $ropeChainPrice = 0;
            $matches = [];
            preg_match( "/\[(.*?)\]/", $color, $matches );
            if(!empty($matches[1])) {
                $colorPrice = StringHelper::getMoney($matches[1]);
                $colorText = trim(str_replace($matches[0],'',$color));
            } else {
                $colorText = trim($color);
            }
            if($ropeChains) {
                foreach ( $ropeChains as $ropeChain ) {
                    $i++;
                    $ropeChainText = '';
                    $ropeChainPrice = 0;
                    $matches = [];
                    preg_match( "/\[(.*?)\]/", $ropeChain, $matches );
                    if(!empty($matches[1])) {
                        $ropeChainPrice = StringHelper::getMoney($matches[1]);
                        $ropeChainText = trim(str_replace($matches[0],'',$ropeChain));
                    } else {
                        $ropeChainText = trim($ropeChain);
                    }
                    $addPrice = $colorPrice + $ropeChainPrice;
                    $name = "Gold Color: $colorText. Rope Chain Option: $ropeChainText. $this->name";
                    $child[] = $this->buildChildProduct($parentFi, $name, $addPrice,$i);
                }
            } else {
                $i++;
                $name = "Gold Color: $colorText. $this->name";
                $addPrice = $colorPrice + $ropeChainPrice;
                $child[] = $this->buildChildProduct($parentFi, $name, $addPrice,$i);
            }
        }
        return $child;
    }

    private function buildChildProduct($parentFi, $name, $addPrice,$i) {
        $fi = clone $parentFi;
        $fi->setMpn(  $this->sku . "_" . $i);
        $fi->setProduct( $name );
        $fi->setCostToUs( $this->price + $addPrice );
        $fi->setListPrice( $this->listPrice + $addPrice );
        $fi->setRAvail( $this->available );
        $fi->setDimX( $this->dims[ 'x' ]);
        $fi->setDimY( $this->dims[ 'y' ] );
        return $fi;
    }
}
