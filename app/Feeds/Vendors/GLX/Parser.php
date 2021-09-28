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

    private array $images = [];
    private float $weight = 0;
    private array $short_description = [];
    private array $attributes = [];


    public function beforeParse(): void
    {
        $offerText = $this->getText('[itemprop="offers"]');
        $matches = [];
        preg_match( '/Width:.+?(.*?)in/is', $offerText, $matches );
        if (!empty($matches[1])) {
            $this->dims['x'] = trim($matches[1]);
        }

        preg_match( '/Height:.+?(.*?)in/is', $offerText, $matches );
        if (!empty($matches[1])) {
            $this->dims['y'] = trim($matches[1]);
        }

        preg_match_all( "/preloadImage\('.*?,'\/\/(.*?)\?v/", $this->node->html(), $matches );
        if (!empty($matches[1])) {
            $this->images = array_map( static function ( $item ) {
                return "https://$item";
            }, $matches[ 1 ] );
        }

        $weight = 0;
        $short_description = [];
        $attributes = [];
        $next_is_descr = false;
        $this->filter( '#viewitem-table tr' )->each( function ( ParserCrawler $item ) use ( &$weight, &$short_description, &$attributes, &$next_is_descr ) {

            if(strstr($item->text(), 'Metal:')) {
                $attributes['metal'] = StringHelper::removeSpaces(str_replace('Metal:','',$item->text()));
            }

            if(strstr($item->text(), 'Item Weight:')) {
                $weight = StringHelper::removeSpaces(str_replace(['Item Weight:','gr.'],'',$item->text()));
                $next_is_descr = true;
            } else if($next_is_descr) {
                $short_description[] = $item->text();
            }


        } );

        $this->weight = $weight;
        $this->short_description = $short_description;
        $this->attributes = $attributes;

    }
    public function getProduct(): string
    {
        return  $this->getText('span[itemprop="name"]');
    }

    public function getImages(): array
    {
        if (!empty($this->images)) return $this->images;
        return array_values(array_unique($this->getLinks('#altviews a')));
    }

    public function getDescription(): string
    {
        return  $this->getHtml('#product_description');
    }

    public function getShortDescription(): array
    {
        return $this->short_description;
    }

    public function getMpn(): string
    {
        return StringHelper::removeSpaces($this->getText('.product_code'));
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
        return $this->dims[ 'x' ] ?? null;
    }

    public function getDimY(): ?float
    {
        return $this->dims[ 'y' ] ?? null;
    }

    public function getOptions(): array
    {
        $colors = $this->getContent( '#options_table select[title="Gold Color"] option' );
        $rope_chains = $this->getContent( '#options_table select[title="Rope Chain Option"] option' );
        $box_chains = $this->getContent( '#options_table select[title="Box Chain Option"] option' );
        $manufacurers_appraisals = $this->getContent( '#options_table select[title="Manufacturer\'s Appraisal"] option' );
        $jewelry_tags = $this->getContent( '#options_table select[title="Jewelry Tag"] option' );

        $options = [];
        foreach ( $colors as $val ) {
            preg_match( "/\[(.*?)\]/", $val, $matches );
            if(empty($matches[1])) {
                $options['colors'][] = $val;
            }
        }
        foreach ( $rope_chains as $val ) {
            preg_match( "/\[(.*?)\]/", $val, $matches );
            if(empty($matches[1])) {
                $options['rope_chains'][] = $val;
            }
        }
        foreach ( $box_chains as $val ) {
            preg_match( "/\[(.*?)\]/", $val, $matches );
            if(empty($matches[1])) {
                $options['box_chains'][] = $val;
            }
        }
        foreach ( $manufacurers_appraisals as $val ) {
            preg_match( "/\[(.*?)\]/", $val, $matches );
            if(empty($matches[1])) {
                $options['manufacurers_appraisals'][] = $val;
            }
        }
        foreach ( $jewelry_tags as $val ) {
            preg_match( "/\[(.*?)\]/", $val, $matches );
            if(empty($matches[1])) {
                $options['jewelry_tags'][] = $val;
            }
        }
        return $options;
    }

    public function getWeight(): ?float
    {
        return $this->weight ?? null;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes ?? null;
    }

}
