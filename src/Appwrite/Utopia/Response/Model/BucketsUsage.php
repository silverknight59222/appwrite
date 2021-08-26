<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;
use stdClass;

class BucketsUsage extends Model
{
    public function __construct()
    {
        $this
            ->addRule('range', [
                'type' => self::TYPE_STRING,
                'description' => 'The time range of the usage stats.',
                'default' => '',
                'example' => '30d',
            ])
            ->addRule('files.create', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for files created.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('files.read', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for files read.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('files.update', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for files updated.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('files.delete', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for files deleted.',
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
        return 'BucketsUsage';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_BUCKETS_USAGE;
    }
}