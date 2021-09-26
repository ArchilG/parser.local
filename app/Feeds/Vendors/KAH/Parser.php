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
    private string $desc = '';
    private array $dims = [
        'x' => null,
        'y' => null,
        'z' => null,
    ];

    public function beforeParse(): void
    {
        if ( $this->exists( '.product-details-area .tab-content' ) ) {
            $descriptionFilter = $this->filter( '.product-details-area .tab-content.selected .components .component' );
            if ( $descriptionFilter->count() === 1 ) {
                $descriptionFilter->filter( '.dimension' )->each( function ( ParserCrawler $item ) {
                    $value = $item->getText( '.dimension-value' );
                    $key = $item->getText( '.dimension-name' );
                    switch ( $key ) {
                        case 'Length:':
                            $this->dims[ 'z' ] = StringHelper::getFloat( $value );
                            break;
                        case 'Width:':
                        case 'Diameter:':
                            $this->dims[ 'x' ] = StringHelper::getFloat( $value );
                            break;
                        case 'Height:':
                            $this->dims[ 'y' ] = StringHelper::getFloat( $value );
                            break;
                    }
                } );
            }
            else if ( $descriptionFilter->count() > 1 ) {
                $this->desc = $this->getHtml( '.tab-content' );
            }
        }

        $src = $this->getAttr( 'script[src*="/component---src-templates-product-js"]', 'src' );
        if ( $src ) {
            $link = new Link( self::MAIN_DOMAIN . $src );
            $data = $this->getVendor()->getDownloader()->get( $link );
            preg_match_all( '/createElement\("li",null,"(.*?)"\)/is', $data->getData(), $matches );
            if ( !empty( $matches[ 1 ] ) ) {
                $this->desc .= "<h3>Care Instructions</h3><ul>" . implode( array_map( static function ( $item ) {
                        return "<li>$item</li>";
                    }, $matches[ 1 ] ) ) . '</ul>';
            }
        }

        if ( !$this->exists( '.product-description ul li' ) ) {
            $this->filter( '.product-description > p' )->each( function ( ParserCrawler $node ) {
                $this->desc .= $node->outerHtml();
            } );
        }
    }

    public function getProduct(): string
    {
        return $this->getText( 'h4.description-title' );
    }

    public function getImages(): array
    {
        $images = $this->getSrcImages( '.single-product-image .slider-slide img' );
        return array_map( static function ( $value ) {
            return UrlHelper::getBaseUrl( $value ) . UrlHelper::getPath( $value );
        }, $images );
    }

    public function getShortDescription(): array
    {
        return $this->getContent( '.product-description ul li' );
    }

    public function getDescription(): string
    {
        return $this->desc;
    }

    public function getMpn(): string
    {
        $data = $this->filter( '.product-data .product-data-point', 0 );
        $value = '';
        $data->each( function ( ParserCrawler $item ) use ( &$value ) {
            $value = $item->filter( '.data-value span' )->text();
        } );
        return $value;
    }

    public function getCostToUs(): float
    {
        $data = $this->filter( '.product-data .product-data-point', 1 );
        $value = 0.0;
        $data->each( function ( ParserCrawler $item ) use ( &$value ) {
            $value = $item->getMoney( '.data-value span' );
        } );
        return $value;
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getCategories(): array
    {
        return array_filter( $this->getContent( '.breadcrumb .breadcrumb-item a' ) );
    }

    public function getDimX(): ?float
    {
        return $this->dims[ 'x' ];
    }

    public function getDimY(): ?float
    {
        return $this->dims[ 'y' ];
    }

    public function getDimZ(): ?float
    {
        return $this->dims[ 'z' ];
    }
}