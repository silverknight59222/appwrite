<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model\AttributeString;

class AttributeEmail extends AttributeString
{
    public function __construct()
    {
        $this
            ->addRule('format', [
                'type' => self::TYPE_STRING,
                'description' => 'String format.',
                'default' => 'email',
                'example' => 'email',
                'array' => false,
                'required' => true,
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
        return 'AttributeEmail';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_ATTRIBUTE_EMAIL;
    }
}