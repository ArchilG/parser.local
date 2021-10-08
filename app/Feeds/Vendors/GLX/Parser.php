<?php

namespace App\Feeds\Vendors\GLX;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [
        'x' => null,
        'y' => null,
        'z' => null,
    ];

    private array $images = [];
    private ?float $weight = null;
    private array $short_description = [];
    private array $attributes = [];
    private bool $continue = false;

    public function beforeParse(): void
    {

        $offer_text = $this->getText( '[itemprop="offers"]' );

        if ( preg_match( '/Width:.+?(.*?)in/is', $offer_text, $matches ) ) {
            $this->dims[ 'x' ] = trim( $matches[ 1 ] );
        }

        if ( preg_match( '/Height:.+?(.*?)in/is', $offer_text, $matches ) ) {
            $this->dims[ 'y' ] = trim( $matches[ 1 ] );
        }

        if ( preg_match( '/Item Length:.+?(.*?)in/is', $offer_text, $matches ) ) {
            $this->dims[ 'z' ] = trim( $matches[ 1 ] );
        }

        if ( preg_match_all( "/preloadImage\('.*?,'\/\/(.*?)\?v/", $this->node->html(), $matches ) ) {
            $this->images = array_map( static function ( $item ) {
                return "https://$item";
            }, $matches[ 1 ] );
        }

        $this->filter( '#viewitem-table tr' )->each( function ( ParserCrawler $item ) {
            if ( str_contains( $item->text(), ':' ) ) {
                [ $key, $value ] = array_map( static fn( $el ) => StringHelper::normalizeSpaceInString( $el ), explode( ':', $item->text() ) );
                if ( $key === 'Item Weight' ) {
                    $this->weight = FeedHelper::convertLbsFromG( StringHelper::getFloat( $value ) );
                }
                else {
                    $this->attributes[ $key ] = $value;
                }
            }
            else if ( $item->text() !== 'Item Information' ) {
                $this->short_description[] = StringHelper::normalizeSpaceInString( $item->text() );
            }
        } );
    }

    public function getProduct(): string
    {
        return $this->getText( 'span[itemprop="name"]' );
    }

    public function getImages(): array
    {
        $this->images = $this->images ?: array_values( array_unique( $this->getLinks( '#altviews a' ) ) );
        return $this->images ?: $this->getSrcImages('#vZoomMagnifierImage');
    }

    public function getDescription(): string
    {
        if($this->exists( '#product_description div' )) {
            $description = '';
            $this->filter( '#product_description div' )->each( function ( ParserCrawler $item ) use (&$description) {
                if(!str_contains($item->text(),'Please feel free to contact us')) {
                    $description .= ' ' . $item->outerHtml();
                }
            });
            return StringHelper::normalizeSpaceInString($description);
        }
        return StringHelper::normalizeSpaceInString(str_replace('SKU: ' . $this->getMpn(), '', $this->getHtml( '#product_description' ))) ?: $this->getText( 'span[itemprop="name"]' );
    }

    public function getShortDescription(): array
    {
        return $this->short_description;
    }

    public function getMpn(): string
    {
        return StringHelper::removeSpaces( $this->getText( '.product_code' ) );
    }

    public function getCostToUs(): float
    {
        return $this->getMoney( '[itemprop="price"]' );
    }

    public function getListPrice(): ?float
    {
        return $this->getMoney( '.list-price' );
    }

    public function getAvail(): ?int
    {
        //return $this->getAttr('[itemprop="availability"]', 'content') === 'InStock' ? self::DEFAULT_AVAIL_NUMBER : 0;
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getCategories(): array
    {
        $categories = $this->getContent( '.vCSS_breadcrumb_td a' );
        array_shift( $categories );
        return array_filter( $categories );
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

    public function getOptions(): array
    {

        $options = [];
        $this->filter( '#options_table select' )->each( function ( ParserCrawler $select ) use ( &$options ) {
            $option_code = str_replace( "'", '', $select->attr( 'title' ) );
            $select->filter( 'option' )->each( function ( ParserCrawler $option ) use ( &$options, $option_code ) {
                $val = $option->text();
                preg_match( "/\[(.*?)]/", $val, $matches );
                if ( empty( $matches[ 1 ] ) ) {
                    $options[ $option_code ][] = $val;
                }
            } );
            if (empty($options[$option_code])) {
                $this->continue = true;
            }
        } );

        return $options;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function getAttributes(): ?array
    {
        $this->attributes[ 'avail' ] = $this->getAttr( '[itemprop="availability"]', 'content' );
        return array_filter($this->attributes) ?: null;
    }

    public function afterParse(FeedItem $fi): void
    {
        if ($this->exists('[name*="TEXTBOX"]') || $this->continue) {
            $fi->setCostToUs(0);
        }
    }

}