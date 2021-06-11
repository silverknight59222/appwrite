<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;
use stdClass;

class Collection extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Collection ID.',
                'default' => '',
                'example' => '',
            ])
            ->addRule('$read', [
                'type' => self::TYPE_STRING,
                'description' => 'Collection read permissions.',
                'default' => '',
                'example' => '',
                'array' => true
            ])
            ->addRule('$write', [
                'type' => self::TYPE_STRING,
                'description' => 'Collection write permissions.',
                'default' => '',
                'example' => '',
                'array' => true
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Collection name.',
                'default' => '',
                'example' => '',
            ])
            ->addRule('attributes', [
                'type' => Response::MODEL_ATTRIBUTE,
                'description' => 'Collection attributes.',
                'default' => [],
                'example' => new stdClass,
                'array' => true
            ])
            ->addRule('indexes', [
                'type' => Response::MODEL_INDEX,
                'description' => 'Collection indexes.',
                'default' => [],
                'example' => new stdClass,
                'array' => true
            ])
            ->addRule('attributesInQueue', [
                'type' => Response::MODEL_ATTRIBUTE,
                'description' => 'Collection attributes in creation queue.',
                'default' => [],
                'example' => new stdClass,
                'array' => true
            ])
            ->addRule('indexesInQueue', [
                'type' => Response::MODEL_INDEX,
                'description' => 'Collection indexes in creation queue.',
                'default' => [],
                'example' => new stdClass,
                'array' => true
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
        return 'Collection';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_COLLECTION;
    }
}