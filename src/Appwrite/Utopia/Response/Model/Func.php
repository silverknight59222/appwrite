<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;
use stdClass;
use Utopia\Database\Document;

class Func extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Function ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('execute', [
                'type' => self::TYPE_STRING,
                'description' => 'Execution permissions.',
                'default' => '',
                'example' => 'role:member',
                'array' => false,
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Function name.',
                'default' => '',
                'example' => 'My Function',
            ])
            ->addRule('dateCreated', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Function creation date in Unix timestamp.',
                'default' => 0,
                'example' => 1592981250,
            ])
            ->addRule('dateUpdated', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Function update date in Unix timestamp.',
                'default' => 0,
                'example' => 1592981257,
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Function status. Possible values: `disabled`, `enabled`',
                'default' => '',
                'example' => 'enabled',
            ])
            ->addRule('runtime', [
                'type' => self::TYPE_STRING,
                'description' => 'Function execution runtime.',
                'default' => '',
                'example' => 'python-3.8',
            ])
            ->addRule('tag', [
                'type' => self::TYPE_STRING,
                'description' => 'Function active tag ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('vars', [
                'type' => self::TYPE_JSON,
                'description' => 'Function environment variables.',
                'default' => new \stdClass(),
                'example' => ['key' => 'value'],
            ])
            ->addRule('events', [
                'type' => self::TYPE_STRING,
                'description' => 'Function trigger events.',
                'default' => [],
                'example' => 'account.create',
                'array' => true,
            ])
            ->addRule('schedule', [
                'type' => self::TYPE_STRING,
                'description' => 'Function execution schedult in CRON format.',
                'default' => '',
                'example' => '5 4 * * *',
            ])
            ->addRule('scheduleNext', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Function next scheduled execution date in Unix timestamp.',
                'default' => 0,
                'example' => 1592981292,
            ])
            ->addRule('schedulePrevious', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Function next scheduled execution date in Unix timestamp.',
                'default' => 0,
                'example' => 1592981237,
            ])
            ->addRule('timeout', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Function execution timeout in seconds.',
                'default' => 15,
                'example' => 1592981237,
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
        return 'Function';
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_FUNCTION;
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function filter(Document $document): Document
    {
        $prefs = $document->getAttribute('vars');
        if($prefs instanceof Document) {
            $prefs = $prefs->getArrayCopy();
        }

        if(is_array($prefs) && empty($prefs)) {
            $document->setAttribute('vars', new stdClass);
        }
        return $document;
    }
}
