<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class Phone extends Model
{
    public function __construct()
    {
        $this
            ->addRule('code', [
                'type' => 'string',
                'description' => 'Phone code.',
                'example' => '+1',
            ])
            ->addRule('countryCode', [
                'type' => 'string',
                'description' => 'Country two-character ISO 3166-1 alpha code.',
                'example' => 'US',
            ])
            ->addRule('countryName', [
                'type' => 'string',
                'description' => 'Country name.',
                'example' => 'United States',
            ])
        ;
    }

    /**
     * Get Name
     * 
     * @return string
     */
    public function getName():string
    {
        return 'Phone';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_PHONE;
    }
}