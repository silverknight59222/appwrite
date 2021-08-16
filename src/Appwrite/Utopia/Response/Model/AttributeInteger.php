<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model\Attribute;

class AttributeInteger extends Attribute
{
    public function __construct()
    {
        $this
            ->addRule('min', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Minimum value to enforce on new documents.',
                'default' => null,
                'example' => 0,
                'array' => false,
                'required' => false,
            ])
            ->addRule('max', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Minimum value to enforce on new documents.',
                'default' => null,
                'example' => 10,
                'array' => false,
                'required' => false,
            ])
            ->addRule('default', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Default value for attribute when not provided. Cannot be set when attribute is required.',
                'default' => null,
                'example' => 10,
                'array' => false,
                'required' => false,
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
        return 'AttributeInteger';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_ATTRIBUTE_INTEGER;
    }
}