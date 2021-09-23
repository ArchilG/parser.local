<?php

namespace App\Feeds\Vendors\KAH;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\UrlHelper;

class Parser extends HtmlParser
{
    public function getProduct(): string
    {
        return $this->getText('h4.description-title');
    }

    public function getImages(): array
    {
        $images = $this->getSrcImages('.single-product-image .slider-slide img');
        $images = array_map(function ($value) {
            return UrlHelper::getBaseUrl($value) . UrlHelper::getPath($value);
        },$images);
        return $images;
    }

    public function getShortDescription(): array
    {
        if ( $this->exists( '.product-description ul li' ) ) {
            return $this->getContent( '.product-description ul li' );
        }
        return [];
    }
   public function getDescription(): string
    {
        $description = '';
        if ( $this->exists( '.product-details-area .tab-content' ) ) {
            $descriptionArr = $this->getContent( '.product-details-area .tab-content' );
            $description = implode("",$descriptionArr);
        }

        return $description;
    }

    public function getMpn(): string
    {
        $data = $this->filter( '.product-data .product-data-point',0 );
        $value = '';
        $data->each( function ( ParserCrawler $item ) use ( &$value) {
            $value = $item->filter( '.data-value span' )->text();
        } );
        return  $value;
    }

    public function getCostToUs(): float
    {
        $data = $this->filter( '.product-data .product-data-point',1 );
        $value = null;
        $data->each( function ( ParserCrawler $item ) use ( &$value ) {
            $value = $item->getMoney( '.data-value span' );
        } );
        return  $value;
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getCategories(): array
    {
        $categories = $this->getContent( '.breadcrumb .breadcrumb-item a' );
//        array_shift( $categories );
        return $categories;
    }
}