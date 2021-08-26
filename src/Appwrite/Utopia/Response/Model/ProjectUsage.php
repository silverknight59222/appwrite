<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;
use stdClass;

class ProjectUsage extends Model
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
            ->addRule('requests', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for number of requests.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('network', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for consumed bandwidth.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('functions', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for function executions.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('documents', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for number of documents.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('collections', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for number of collections.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('users', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for number of users.',
                'default' => [],
                'example' => new stdClass,
                'array' => true 
            ])
            ->addRule('functions', [
                'type' => Response::MODEL_METRIC_LIST,
                'description' => 'Aggregated stats for the occupied storage size.',
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
        return 'ProjectUsage';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_PROJECT_USAGE;
    }
}