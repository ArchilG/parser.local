<?php

namespace App\Feeds\Vendors\KAH;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;
use App\Helpers\UrlHelper;
use phpDocumentor\Reflection\DocBlock\Tags\Link;

class Parser extends HtmlParser
{
    private const MAIN_DOMAIN = 'https://www.kalmarhome.com';
    private string $descr = '';
    private ?array $attrs = null;
    public function beforeParse(): void
    {
        if ( $this->exists( '.product-details-area .tab-content' ) ) {

            $description = '';
            $descriptionFilter = $this->filter( '.product-details-area .tab-content.selected .components .component' );
            if($descriptionFilter->count()) {
                if($descriptionFilter->count() == 1) {
                    $attrs = [];
                    $descriptionFilter->filter('.dimension')->each( function ( ParserCrawler $item ) use ( &$attrs) {
                        $attrs[ StringHelper::normalizeSpaceInString( $item->filter( '.dimension-name' )->text() ) ] = StringHelper::normalizeSpaceInString( $item->filter( '.dimension-value' )->text() );
                    } );
                    $this->attrs = $attrs;
                } else {
                    $description = '<p>Product Dimensions</p>';
                    $descriptionFilter->each( function ( ParserCrawler $item ) use ( &$description) {
                        $description .= '<p>' . $item->filter( 'h5' )->text() . '</p><ul>';
                        $item->filter('.dimension')->each( function ( ParserCrawler $subitem ) use ( &$description) {
                            $description .= '<li>' . $subitem->filter( '.dimension-name' )->text() . ': ' . $subitem->filter( '.dimension-value' )->text() . '</li>';
                        } );
                        $description .= '</ul>';
                    } );

                    $this->descr .= $description;
                }
            }

        }

        $src = $this->getAttr('script[src*="/component---src-templates-product-js"]','src');
        if($src) {
            $link = new Link(self::MAIN_DOMAIN . $src);
            $data = $this->getVendor()->getDownloader()->get($link);
            preg_match_all( '/createElement\("li",null,"(.*?)"\)/is', $data->getData(), $matches );
            if(!empty($matches[1])) {
                $this->descr .= "<h3>Care Instructions</h3><ul>" . implode(array_map(function($item) {return "<li>{$item}</li>";},$matches[1])) . '</ul>';
            }
        }

    }
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
        return $this->descr;
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
        return array_filter($categories);
    }

    public function getAttributes(): ?array
    {
        return $this->attrs ?? null;
    }
}