<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model\Attribute;

class AttributeFloat extends Attribute
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->addRule('min', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Minimum value to enforce for new documents.',
                'default' => null,
                'example' => 1,
                'array' => false,
                'require' => false,
            ])
            ->addRule('max', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Maximum value to enforce for new documents.',
                'default' => null,
                'example' => 10,
                'array' => false,
                'require' => false,
            ])
            ->addRule('default', [
                'type' => self::TYPE_FLOAT,
                'description' => 'Default value for attribute when not provided. Cannot be set when attribute is required.',
                'default' => null,
                'example' => 2.5,
                'array' => false,
                'require' => false,
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
        return 'AttributeFloat';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_ATTRIBUTE_FLOAT;
    }
}