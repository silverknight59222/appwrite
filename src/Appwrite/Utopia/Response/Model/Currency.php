<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class Currency extends Model
{
    public function __construct()
    {
        $this
            ->addRule('symbol', [
                'type' => self::TYPE_STRING,
                'description' => 'Currency symbol.',
                'example' => '$',
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Currency name.',
                'example' => 'US dollar',
            ])
            ->addRule('symbolNative', [
                'type' => self::TYPE_STRING,
                'description' => 'Currency native symbol.',
                'example' => '$',
            ])
            ->addRule('decimalDigits', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Number of decimal digits.',
                'example' => 2,
            ])
            ->addRule('rounding', [
                'type' => self::TYPE_FLOAT,
                'description' => 'Currency digit rounding.',
                'example' => 0,
            ])
            ->addRule('code', [
                'type' => self::TYPE_STRING,
                'description' => 'Currency code in [ISO 4217-1](http://en.wikipedia.org/wiki/ISO_4217) three-character format.',
                'example' => 'USD',
            ])
            ->addRule('namePlural', [
                'type' => self::TYPE_STRING,
                'description' => 'Currency plural name',
                'example' => 'US dollars',
            ])
            // ->addRule('locations', [
            //     'type' => self::TYPE_STRING,
            //     'description' => 'Currency locations list. List of location in two-character ISO 3166-1 alpha code.',
            //     'example' => ['US'],
            //     'array' => true,
            // ])
        ;
    }

    /**
     * Get Name
     * 
     * @return string
     */
    public function getName():string
    {
        return 'Currency';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_CURRENCY;
    }
}