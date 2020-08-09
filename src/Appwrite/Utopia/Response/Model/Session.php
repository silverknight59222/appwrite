<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class Session extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => 'string',
                'description' => 'Session ID.',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('expire', [
                'type' => 'integer',
                'description' => 'Session expiration date in Unix timestamp.',
                'example' => 1592981250,
            ])
            ->addRule('ip', [
                'type' => 'string',
                'description' => 'IP session in use when the session was created.',
                'example' => '127.0.0.1',
            ])
            ->addRule('osCode', [
                'type' => 'string',
                'description' => 'Operating system code name. View list of [available options](https://github.com/appwrite/appwrite/blob/master/docs/lists/os.json).',
                'example' => 'Mac',
            ])
            ->addRule('osName', [
                'type' => 'string',
                'description' => 'Operating system name.',
                'example' => 'Mac',
            ])
            ->addRule('osVersion', [
                'type' => 'string',
                'description' => 'Operating system version.',
                'example' => 'Mac',
            ])
            ->addRule('clientType', [
                'type' => 'string',
                'description' => 'Client type.',
                'example' => 'browser',
            ])
            ->addRule('clientCode', [
                'type' => 'string',
                'description' => 'Client code name. View list of [available options](https://github.com/appwrite/appwrite/blob/master/docs/lists/clients.json).',
                'example' => 'CM',
            ])
            ->addRule('clientName', [
                'type' => 'string',
                'description' => 'Client name.',
                'example' => 'Chrome Mobile iOS',
            ])
            ->addRule('clientVersion', [
                'type' => 'string',
                'description' => 'Client version.',
                'example' => '84.0',
            ])
            ->addRule('clientEngine', [
                'type' => 'string',
                'description' => 'Client engine name.',
                'example' => 'WebKit',
            ])
            ->addRule('clientEngineVersion', [
                'type' => 'string',
                'description' => 'Client engine name.',
                'example' => '605.1.15',
            ])
            ->addRule('deviceName', [
                'type' => 'string',
                'description' => 'Device name.',
                'example' => 'smartphone',
            ])
            ->addRule('deviceBrand', [
                'type' => 'string',
                'description' => 'Device brand name.',
                'example' => 'Google',
            ])
            ->addRule('deviceModel', [
                'type' => 'string',
                'description' => 'Device model name.',
                'example' => 'Nexus 5',
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
            ->addRule('current', [
                'type' => 'boolean',
                'description' => 'Returns true if this the current user session.',
                'example' => true,
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
        return 'Session';
    }

    /**
     * Get Collection
     * 
     * @return string
     */
    public function getType():string
    {
        return Response::MODEL_SESSION;
    }
}