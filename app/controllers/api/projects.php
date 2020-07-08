<?php

use Utopia\App;
use Utopia\Exception;
use Utopia\Response;
use Utopia\Validator\ArrayList;
use Utopia\Validator\Boolean;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;
use Utopia\Validator\URL;
use Utopia\Validator\Range;
use Utopia\Config\Config;
use Utopia\Domains\Domain;
use Appwrite\Auth\Auth;
use Appwrite\Task\Validator\Cron;
use Appwrite\Database\Database;
use Appwrite\Database\Document;
use Appwrite\Database\Validator\UID;
use Appwrite\OpenSSL\OpenSSL;
use Appwrite\Network\Validator\CNAME;
use Appwrite\Network\Validator\Domain as DomainValidator;
use Cron\CronExpression;

App::post('/v1/projects')
    ->desc('Create Project')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'create')
    ->param('name', null, function () { return new Text(100); }, 'Project name.')
    ->param('teamId', '', function () { return new UID(); }, 'Team unique ID.')
    ->param('description', '', function () { return new Text(255); }, 'Project description.', true)
    ->param('logo', '', function () { return new Text(1024); }, 'Project logo.', true)
    ->param('url', '', function () { return new URL(); }, 'Project URL.', true)
    ->param('legalName', '', function () { return new Text(256); }, 'Project legal Name.', true)
    ->param('legalCountry', '', function () { return new Text(256); }, 'Project legal Country.', true)
    ->param('legalState', '', function () { return new Text(256); }, 'Project legal State.', true)
    ->param('legalCity', '', function () { return new Text(256); }, 'Project legal City.', true)
    ->param('legalAddress', '', function () { return new Text(256); }, 'Project legal Address.', true)
    ->param('legalTaxId', '', function () { return new Text(256); }, 'Project legal Tax ID.', true)
    ->action(function ($name, $teamId, $description, $logo, $url, $legalName, $legalCountry, $legalState, $legalCity, $legalAddress, $legalTaxId, $response, $consoleDB, $projectDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */
        /** @var Appwrite\Database\Database $projectDB */

        $team = $projectDB->getDocument($teamId);

        if (empty($team->getId()) || Database::SYSTEM_COLLECTION_TEAMS != $team->getCollection()) {
            throw new Exception('Team not found', 404);
        }

        $project = $consoleDB->createDocument(
            [
                '$collection' => Database::SYSTEM_COLLECTION_PROJECTS,
                '$permissions' => [
                    'read' => ['team:'.$teamId],
                    'write' => ['team:'.$teamId.'/owner', 'team:'.$teamId.'/developer'],
                ],
                'name' => $name,
                'description' => $description,
                'logo' => $logo,
                'url' => $url,
                'legalName' => $legalName,
                'legalCountry' => $legalCountry,
                'legalState' => $legalState,
                'legalCity' => $legalCity,
                'legalAddress' => $legalAddress,
                'legalTaxId' => $legalTaxId,
                'teamId' => $team->getId(),
                'platforms' => [],
                'webhooks' => [],
                'keys' => [],
                'tasks' => [],
            ]
        );

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $consoleDB->createNamespace($project->getId());

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($project->getArrayCopy())
        ;
    }, ['response', 'consoleDB', 'projectDB']);

App::get('/v1/projects')
    ->desc('List Projects')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'list')
    ->param('search', '', function () { return new Text(256); }, 'Search term to filter your list results.', true)
    ->param('limit', 25, function () { return new Range(0, 100); }, 'Results limit value. By default will return maximum 25 results. Maximum of 100 results allowed per request.', true)
    ->param('offset', 0, function () { return new Range(0, 2000); }, 'Results offset. The default value is 0. Use this param to manage pagination.', true)
    ->param('orderType', 'ASC', function () { return new WhiteList(['ASC', 'DESC']); }, 'Order result by ASC or DESC order.', true)
    ->action(function ($search, $limit, $offset, $orderType, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $results = $consoleDB->getCollection([
            'limit' => $limit,
            'offset' => $offset,
            'orderField' => 'registration',
            'orderType' => $orderType,
            'orderCast' => 'int',
            'search' => $search,
            'filters' => [
                '$collection='.Database::SYSTEM_COLLECTION_PROJECTS,
            ],
        ]);
        foreach ($results as $project) {
            foreach (Config::getParam('providers') as $provider => $node) {
                $secret = \json_decode($project->getAttribute('usersOauth2'.\ucfirst($provider).'Secret', '{}'), true);

                if (!empty($secret) && isset($secret['version'])) {
                    $key = App::getEnv('_APP_OPENSSL_KEY_V'.$secret['version']);
                    $project->setAttribute('usersOauth2'.\ucfirst($provider).'Secret', OpenSSL::decrypt($secret['data'], $secret['method'], $key, 0, \hex2bin($secret['iv']), \hex2bin($secret['tag'])));
                }
            }
        }

        $response->json(['sum' => $consoleDB->getSum(), 'projects' => $results]);
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId')
    ->desc('Get Project')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'get')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        foreach (Config::getParam('providers') as $provider => $node) {
            $secret = \json_decode($project->getAttribute('usersOauth2'.\ucfirst($provider).'Secret', '{}'), true);

            if (!empty($secret) && isset($secret['version'])) {
                $key = App::getEnv('_APP_OPENSSL_KEY_V'.$secret['version']);
                $project->setAttribute('usersOauth2'.\ucfirst($provider).'Secret', OpenSSL::decrypt($secret['data'], $secret['method'], $key, 0, \hex2bin($secret['iv']), \hex2bin($secret['tag'])));
            }
        }

        $response->json($project->getArrayCopy());
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/usage')
    ->desc('Get Project')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getUsage')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->param('range', 'last30', function () { return new WhiteList(['daily', 'monthly', 'last30', 'last90']); }, 'Date range.', true)
    ->action(function ($projectId, $range, $response, $consoleDB, $projectDB, $register) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */
        /** @var Appwrite\Database\Database $projectDB */
        /** @var Utopia\Registry\Registry $register */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $period = [
            'daily' => [
                'start' => DateTime::createFromFormat('U', \strtotime('today')),
                'end' => DateTime::createFromFormat('U', \strtotime('tomorrow')),
                'group' => '1m',
            ],
            'monthly' => [
                'start' => DateTime::createFromFormat('U', \strtotime('midnight first day of this month')),
                'end' => DateTime::createFromFormat('U', \strtotime('midnight last day of this month')),
                'group' => '1d',
            ],
            'last30' => [
                'start' => DateTime::createFromFormat('U', \strtotime('-30 days')),
                'end' => DateTime::createFromFormat('U', \strtotime('tomorrow')),
                'group' => '1d',
            ],
            'last90' => [
                'start' => DateTime::createFromFormat('U', \strtotime('-90 days')),
                'end' => DateTime::createFromFormat('U', \strtotime('today')),
                'group' => '1d',
            ],
            // 'yearly' => [
            //     'start' => DateTime::createFromFormat('U', strtotime('midnight first day of january')),
            //     'end' => DateTime::createFromFormat('U', strtotime('midnight last day of december')),
            //     'group' => '4w',
            // ],
        ];

        $client = $register->get('influxdb');

        $requests = [];
        $network = [];

        if ($client) {
            $start = $period[$range]['start']->format(DateTime::RFC3339);
            $end = $period[$range]['end']->format(DateTime::RFC3339);
            $database = $client->selectDB('telegraf');

            // Requests
            $result = $database->query('SELECT sum(value) AS "value" FROM "appwrite_usage_requests_all" WHERE time > \''.$start.'\' AND time < \''.$end.'\' AND "metric_type"=\'counter\' AND "project"=\''.$project->getId().'\' GROUP BY time('.$period[$range]['group'].') FILL(null)');
            $points = $result->getPoints();

            foreach ($points as $point) {
                $requests[] = [
                    'value' => (!empty($point['value'])) ? $point['value'] : 0,
                    'date' => \strtotime($point['time']),
                ];
            }

            // Network
            $result = $database->query('SELECT sum(value) AS "value" FROM "appwrite_usage_network_all" WHERE time > \''.$start.'\' AND time < \''.$end.'\' AND "metric_type"=\'counter\' AND "project"=\''.$project->getId().'\' GROUP BY time('.$period[$range]['group'].') FILL(null)');
            $points = $result->getPoints();

            foreach ($points as $point) {
                $network[] = [
                    'value' => (!empty($point['value'])) ? $point['value'] : 0,
                    'date' => \strtotime($point['time']),
                ];
            }
        }

        // Users

        $projectDB->getCollection([
            'limit' => 0,
            'offset' => 0,
            'filters' => [
                '$collection='.Database::SYSTEM_COLLECTION_USERS,
            ],
        ]);

        $usersTotal = $projectDB->getSum();

        // Documents

        $collections = $projectDB->getCollection([
            'limit' => 100,
            'offset' => 0,
            'filters' => [
                '$collection='.Database::SYSTEM_COLLECTION_COLLECTIONS,
            ],
        ]);

        $collectionsTotal = $projectDB->getSum();

        $documents = [];

        foreach ($collections as $collection) {
            $result = $projectDB->getCollection([
                'limit' => 0,
                'offset' => 0,
                'filters' => [
                    '$collection='.$collection['$id'],
                ],
            ]);

            $documents[] = ['name' => $collection['name'], 'total' => $projectDB->getSum()];
        }

        // Tasks
        $tasksTotal = \count($project->getAttribute('tasks', []));

        $response->json([
            'requests' => [
                'data' => $requests,
                'total' => \array_sum(\array_map(function ($item) {
                    return $item['value'];
                }, $requests)),
            ],
            'network' => [
                'data' => $network,
                'total' => \array_sum(\array_map(function ($item) {
                    return $item['value'];
                }, $network)),
            ],
            'collections' => [
                'data' => $collections,
                'total' => $collectionsTotal,
            ],
            'documents' => [
                'data' => $documents,
                'total' => \array_sum(\array_map(function ($item) {
                    return $item['total'];
                }, $documents)),
            ],
            'users' => [
                'data' => [],
                'total' => $usersTotal,
            ],
            'tasks' => [
                'data' => [],
                'total' => $tasksTotal,
            ],
            'storage' => [
                'total' => $projectDB->getCount(
                    [
                        'filters' => [
                            '$collection='.Database::SYSTEM_COLLECTION_FILES,
                        ],
                    ]
                ),
            ],
        ]);
    }, ['response', 'consoleDB', 'projectDB', 'register']);

App::patch('/v1/projects/:projectId')
    ->desc('Update Project')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'update')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->param('name', null, function () { return new Text(100); }, 'Project name.')
    ->param('description', '', function () { return new Text(255); }, 'Project description.', true)
    ->param('logo', '', function () { return new Text(1024); }, 'Project logo.', true)
    ->param('url', '', function () { return new URL(); }, 'Project URL.', true)
    ->param('legalName', '', function () { return new Text(256); }, 'Project legal name.', true)
    ->param('legalCountry', '', function () { return new Text(256); }, 'Project legal country..', true)
    ->param('legalState', '', function () { return new Text(256); }, 'Project legal state.', true)
    ->param('legalCity', '', function () { return new Text(256); }, 'Project legal city.', true)
    ->param('legalAddress', '', function () { return new Text(256); }, 'Project legal address.', true)
    ->param('legalTaxId', '', function () { return new Text(256); }, 'Project legal tax ID.', true)
    ->action(function ($projectId, $name, $description, $logo, $url, $legalName, $legalCountry, $legalState, $legalCity, $legalAddress, $legalTaxId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $project = $consoleDB->updateDocument(\array_merge($project->getArrayCopy(), [
            'name' => $name,
            'description' => $description,
            'logo' => $logo,
            'url' => $url,
            'legalName' => $legalName,
            'legalCountry' => $legalCountry,
            'legalState' => $legalState,
            'legalCity' => $legalCity,
            'legalAddress' => $legalAddress,
            'legalTaxId' => $legalTaxId,
        ]));

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response->json($project->getArrayCopy());
    }, ['response', 'consoleDB']);

App::patch('/v1/projects/:projectId/oauth2')
    ->desc('Update Project OAuth2')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updateOAuth2')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->param('provider', '', function () { return new WhiteList(\array_keys(Config::getParam('providers'))); }, 'Provider Name', false)
    ->param('appId', '', function () { return new Text(256); }, 'Provider app ID.', true)
    ->param('secret', '', function () { return new text(512); }, 'Provider secret key.', true)
    ->action(function ($projectId, $provider, $appId, $secret, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        $secret = \json_encode([
            'data' => OpenSSL::encrypt($secret, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => \bin2hex($iv),
            'tag' => \bin2hex($tag),
            'version' => '1',
        ]);

        $project = $consoleDB->updateDocument(\array_merge($project->getArrayCopy(), [
            'usersOauth2'.\ucfirst($provider).'Appid' => $appId,
            'usersOauth2'.\ucfirst($provider).'Secret' => $secret,
        ]));

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response->json($project->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId')
    ->desc('Delete Project')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'delete')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->param('password', '', function () { return new UID(); }, 'Your user password for confirmation. Must be between 6 to 32 chars.')
    ->action(function ($projectId, $password, $response, $user, $consoleDB, $deletes) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Document $user */
        /** @var Appwrite\Database\Database $consoleDB */
        /** @var Appwrite\Event\Event $deletes */

        if (!Auth::passwordVerify($password, $user->getAttribute('password'))) { // Double check user password
            throw new Exception('Invalid credentials', 401);
        }

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $deletes->setParam('document', $project->getArrayCopy());

        foreach (['keys', 'webhooks', 'tasks', 'platforms', 'domains'] as $key) { // Delete all children (keys, webhooks, tasks [stop tasks?], platforms)
            $list = $project->getAttribute('webhooks', []);

            foreach ($list as $document) { /* @var $document Document */
                if (!$consoleDB->deleteDocument($projectId)) {
                    throw new Exception('Failed to remove project document ('.$key.')] from DB', 500);
                }
            }
        }
        
        if (!$consoleDB->deleteDocument($project->getAttribute('teamId', null))) {
            throw new Exception('Failed to remove project team from DB', 500);
        }

        if (!$consoleDB->deleteDocument($projectId)) {
            throw new Exception('Failed to remove project from DB', 500);
        }

        $response->noContent();
    }, ['response', 'user', 'consoleDB', 'deletes']);

// Webhooks

App::post('/v1/projects/:projectId/webhooks')
    ->desc('Create Webhook')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'createWebhook')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Webhook name.')
    ->param('events', null, function () { return new ArrayList(new Text(256)); }, 'Webhook events list.')
    ->param('url', null, function () { return new Text(2000); }, 'Webhook URL.')
    ->param('security', false, function () { return new Boolean(true); }, 'Certificate verification, false for disabled or true for enabled.')
    ->param('httpUser', '', function () { return new Text(256); }, 'Webhook HTTP user.', true)
    ->param('httpPass', '', function () { return new Text(256); }, 'Webhook HTTP password.', true)
    ->action(function ($projectId, $name, $events, $url, $security, $httpUser, $httpPass, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $security = ($security === '1' || $security === 'true' || $security === 1 || $security === true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        $httpPass = \json_encode([
            'data' => OpenSSL::encrypt($httpPass, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => \bin2hex($iv),
            'tag' => \bin2hex($tag),
            'version' => '1',
        ]);

        $webhook = $consoleDB->createDocument([
            '$collection' => Database::SYSTEM_COLLECTION_WEBHOOKS,
            '$permissions' => [
                'read' => ['team:'.$project->getAttribute('teamId', null)],
                'write' => ['team:'.$project->getAttribute('teamId', null).'/owner', 'team:'.$project->getAttribute('teamId', null).'/developer'],
            ],
            'name' => $name,
            'events' => $events,
            'url' => $url,
            'security' => (int) $security,
            'httpUser' => $httpUser,
            'httpPass' => $httpPass,
        ]);

        if (false === $webhook) {
            throw new Exception('Failed saving webhook to DB', 500);
        }

        $project->setAttribute('webhooks', $webhook, Document::SET_TYPE_APPEND);

        $project = $consoleDB->updateDocument($project->getArrayCopy());

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($webhook->getArrayCopy())
        ;
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/webhooks')
    ->desc('List Webhooks')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'listWebhooks')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $webhooks = $project->getAttribute('webhooks', []);

        foreach ($webhooks as $webhook) { /* @var $webhook Document */
            $httpPass = \json_decode($webhook->getAttribute('httpPass', '{}'), true);

            if (empty($httpPass) || !isset($httpPass['version'])) {
                continue;
            }

            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$httpPass['version']);

            $webhook->setAttribute('httpPass', OpenSSL::decrypt($httpPass['data'], $httpPass['method'], $key, 0, \hex2bin($httpPass['iv']), \hex2bin($httpPass['tag'])));
        }

        $response->json($webhooks);
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/webhooks/:webhookId')
    ->desc('Get Webhook')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getWebhook')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('webhookId', null, function () { return new UID(); }, 'Webhook unique ID.')
    ->action(function ($projectId, $webhookId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $webhook = $project->search('$id', $webhookId, $project->getAttribute('webhooks', []));

        if (empty($webhook) || !$webhook instanceof Document) {
            throw new Exception('Webhook not found', 404);
        }

        $httpPass = \json_decode($webhook->getAttribute('httpPass', '{}'), true);

        if (!empty($httpPass) && isset($httpPass['version'])) {
            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$httpPass['version']);
            $webhook->setAttribute('httpPass', OpenSSL::decrypt($httpPass['data'], $httpPass['method'], $key, 0, \hex2bin($httpPass['iv']), \hex2bin($httpPass['tag'])));
        }

        $response->json($webhook->getArrayCopy());
    }, ['response', 'consoleDB']);

App::put('/v1/projects/:projectId/webhooks/:webhookId')
    ->desc('Update Webhook')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updateWebhook')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('webhookId', null, function () { return new UID(); }, 'Webhook unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Webhook name.')
    ->param('events', null, function () { return new ArrayList(new Text(256)); }, 'Webhook events list.')
    ->param('url', null, function () { return new Text(2000); }, 'Webhook URL.')
    ->param('security', false, function () { return new Boolean(true); }, 'Certificate verification, false for disabled or true for enabled.')    ->param('httpUser', '', function () { return new Text(256); }, 'Webhook HTTP user.', true)
    ->param('httpPass', '', function () { return new Text(256); }, 'Webhook HTTP password.', true)
    ->action(function ($projectId, $webhookId, $name, $events, $url, $security, $httpUser, $httpPass, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $security = ($security === '1' || $security === 'true' || $security === 1 || $security === true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        $httpPass = \json_encode([
            'data' => OpenSSL::encrypt($httpPass, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => \bin2hex($iv),
            'tag' => \bin2hex($tag),
            'version' => '1',
        ]);

        $webhook = $project->search('$id', $webhookId, $project->getAttribute('webhooks', []));

        if (empty($webhook) || !$webhook instanceof Document) {
            throw new Exception('Webhook not found', 404);
        }

        $webhook
            ->setAttribute('name', $name)
            ->setAttribute('events', $events)
            ->setAttribute('url', $url)
            ->setAttribute('security', (int) $security)
            ->setAttribute('httpUser', $httpUser)
            ->setAttribute('httpPass', $httpPass)
        ;

        if (false === $consoleDB->updateDocument($webhook->getArrayCopy())) {
            throw new Exception('Failed saving webhook to DB', 500);
        }

        $response->json($webhook->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId/webhooks/:webhookId')
    ->desc('Delete Webhook')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'deleteWebhook')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('webhookId', null, function () { return new UID(); }, 'Webhook unique ID.')
    ->action(function ($projectId, $webhookId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $webhook = $project->search('$id', $webhookId, $project->getAttribute('webhooks', []));

        if (empty($webhook) || !$webhook instanceof Document) {
            throw new Exception('Webhook not found', 404);
        }

        if (!$consoleDB->deleteDocument($webhook->getId())) {
            throw new Exception('Failed to remove webhook from DB', 500);
        }

        $response->noContent();
    }, ['response', 'consoleDB']);

// Keys

App::post('/v1/projects/:projectId/keys')
    ->desc('Create Key')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'createKey')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Key name.')
    ->param('scopes', null, function () { return new ArrayList(new WhiteList(Config::getParam('scopes'))); }, 'Key scopes list.')
    ->action(function ($projectId, $name, $scopes, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $key = $consoleDB->createDocument([
            '$collection' => Database::SYSTEM_COLLECTION_KEYS,
            '$permissions' => [
                'read' => ['team:'.$project->getAttribute('teamId', null)],
                'write' => ['team:'.$project->getAttribute('teamId', null).'/owner', 'team:'.$project->getAttribute('teamId', null).'/developer'],
            ],
            'name' => $name,
            'scopes' => $scopes,
            'secret' => \bin2hex(\random_bytes(128)),
        ]);

        if (false === $key) {
            throw new Exception('Failed saving key to DB', 500);
        }

        $project->setAttribute('keys', $key, Document::SET_TYPE_APPEND);

        $project = $consoleDB->updateDocument($project->getArrayCopy());

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($key->getArrayCopy())
        ;
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/keys')
    ->desc('List Keys')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'listKeys')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */
        
        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $response->json($project->getAttribute('keys', [])); //FIXME make sure array objects return correctly
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/keys/:keyId')
    ->desc('Get Key')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getKey')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('keyId', null, function () { return new UID(); }, 'Key unique ID.')
    ->action(function ($projectId, $keyId, $response, $consoleDB) {
        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $key = $project->search('$id', $keyId, $project->getAttribute('keys', []));

        if (empty($key) || !$key instanceof Document) {
            throw new Exception('Key not found', 404);
        }

        $response->json($key->getArrayCopy());
    }, ['response', 'consoleDB']);

App::put('/v1/projects/:projectId/keys/:keyId')
    ->desc('Update Key')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updateKey')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('keyId', null, function () { return new UID(); }, 'Key unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Key name.')
    ->param('scopes', null, function () { return new ArrayList(new WhiteList(Config::getParam('scopes'))); }, 'Key scopes list')
    ->action(function ($projectId, $keyId, $name, $scopes, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $key = $project->search('$id', $keyId, $project->getAttribute('keys', []));

        if (empty($key) || !$key instanceof Document) {
            throw new Exception('Key not found', 404);
        }

        $key
            ->setAttribute('name', $name)
            ->setAttribute('scopes', $scopes)
        ;

        if (false === $consoleDB->updateDocument($key->getArrayCopy())) {
            throw new Exception('Failed saving key to DB', 500);
        }

        $response->json($key->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId/keys/:keyId')
    ->desc('Delete Key')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'deleteKey')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('keyId', null, function () { return new UID(); }, 'Key unique ID.')
    ->action(function ($projectId, $keyId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $key = $project->search('$id', $keyId, $project->getAttribute('keys', []));

        if (empty($key) || !$key instanceof Document) {
            throw new Exception('Key not found', 404);
        }

        if (!$consoleDB->deleteDocument($key->getId())) {
            throw new Exception('Failed to remove key from DB', 500);
        }

        $response->noContent();
    }, ['response', 'consoleDB']);

// Tasks

App::post('/v1/projects/:projectId/tasks')
    ->desc('Create Task')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'createTask')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Task name.')
    ->param('status', null, function () { return new WhiteList(['play', 'pause']); }, 'Task status.')
    ->param('schedule', null, function () { return new Cron(); }, 'Task schedule CRON syntax.')
    ->param('security', false, function () { return new Boolean(true); }, 'Certificate verification, false for disabled or true for enabled.')    ->param('httpMethod', '', function () { return new WhiteList(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT']); }, 'Task HTTP method.')
    ->param('httpUrl', '', function () { return new URL(); }, 'Task HTTP URL')
    ->param('httpHeaders', null, function () { return new ArrayList(new Text(256)); }, 'Task HTTP headers list.', true)
    ->param('httpUser', '', function () { return new Text(256); }, 'Task HTTP user.', true)
    ->param('httpPass', '', function () { return new Text(256); }, 'Task HTTP password.', true)
    ->action(function ($projectId, $name, $status, $schedule, $security, $httpMethod, $httpUrl, $httpHeaders, $httpUser, $httpPass, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $cron = CronExpression::factory($schedule);
        $next = ($status == 'play') ? $cron->getNextRunDate()->format('U') : null;

        $security = ($security === '1' || $security === 'true' || $security === 1 || $security === true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        $httpPass = \json_encode([
            'data' => OpenSSL::encrypt($httpPass, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => \bin2hex($iv),
            'tag' => \bin2hex($tag),
            'version' => '1',
        ]);

        $task = $consoleDB->createDocument([
            '$collection' => Database::SYSTEM_COLLECTION_TASKS,
            '$permissions' => [
                'read' => ['team:'.$project->getAttribute('teamId', null)],
                'write' => ['team:'.$project->getAttribute('teamId', null).'/owner', 'team:'.$project->getAttribute('teamId', null).'/developer'],
            ],
            'name' => $name,
            'status' => $status,
            'schedule' => $schedule,
            'updated' => \time(),
            'previous' => null,
            'next' => $next,
            'security' => (int) $security,
            'httpMethod' => $httpMethod,
            'httpUrl' => $httpUrl,
            'httpHeaders' => $httpHeaders,
            'httpUser' => $httpUser,
            'httpPass' => $httpPass,
            'log' => '{}',
            'failures' => 0,
        ]);

        if (false === $task) {
            throw new Exception('Failed saving tasks to DB', 500);
        }

        $project->setAttribute('tasks', $task, Document::SET_TYPE_APPEND);

        $project = $consoleDB->updateDocument($project->getArrayCopy());

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        if ($next) {
            ResqueScheduler::enqueueAt($next, 'v1-tasks', 'TasksV1', $task->getArrayCopy());
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($task->getArrayCopy())
        ;
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/tasks')
    ->desc('List Tasks')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'listTasks')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $tasks = $project->getAttribute('tasks', []);

        foreach ($tasks as $task) { /* @var $task Document */
            $httpPass = \json_decode($task->getAttribute('httpPass', '{}'), true);

            if (empty($httpPass) || !isset($httpPass['version'])) {
                continue;
            }

            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$httpPass['version']);

            $task->setAttribute('httpPass', OpenSSL::decrypt($httpPass['data'], $httpPass['method'], $key, 0, \hex2bin($httpPass['iv']), \hex2bin($httpPass['tag'])));
        }

        $response->json($tasks);
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/tasks/:taskId')
    ->desc('Get Task')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getTask')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('taskId', null, function () { return new UID(); }, 'Task unique ID.')
    ->action(function ($projectId, $taskId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $task = $project->search('$id', $taskId, $project->getAttribute('tasks', []));

        if (empty($task) || !$task instanceof Document) {
            throw new Exception('Task not found', 404);
        }

        $httpPass = \json_decode($task->getAttribute('httpPass', '{}'), true);

        if (!empty($httpPass) && isset($httpPass['version'])) {
            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$httpPass['version']);
            $task->setAttribute('httpPass', OpenSSL::decrypt($httpPass['data'], $httpPass['method'], $key, 0, \hex2bin($httpPass['iv']), \hex2bin($httpPass['tag'])));
        }

        $response->json($task->getArrayCopy());
    }, ['response', 'consoleDB']);

App::put('/v1/projects/:projectId/tasks/:taskId')
    ->desc('Update Task')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updateTask')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('taskId', null, function () { return new UID(); }, 'Task unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Task name.')
    ->param('status', null, function () { return new WhiteList(['play', 'pause']); }, 'Task status.')
    ->param('schedule', null, function () { return new Cron(); }, 'Task schedule CRON syntax.')
    ->param('security', false, function () { return new Boolean(true); }, 'Certificate verification, false for disabled or true for enabled.')
    ->param('httpMethod', '', function () { return new WhiteList(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT']); }, 'Task HTTP method.')
    ->param('httpUrl', '', function () { return new URL(); }, 'Task HTTP URL.')
    ->param('httpHeaders', null, function () { return new ArrayList(new Text(256)); }, 'Task HTTP headers list.', true)
    ->param('httpUser', '', function () { return new Text(256); }, 'Task HTTP user.', true)
    ->param('httpPass', '', function () { return new Text(256); }, 'Task HTTP password.', true)
    ->action(function ($projectId, $taskId, $name, $status, $schedule, $security, $httpMethod, $httpUrl, $httpHeaders, $httpUser, $httpPass, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $task = $project->search('$id', $taskId, $project->getAttribute('tasks', []));

        if (empty($task) || !$task instanceof Document) {
            throw new Exception('Task not found', 404);
        }

        $cron = CronExpression::factory($schedule);
        $next = ($status == 'play') ? $cron->getNextRunDate()->format('U') : null;

        $security = ($security === '1' || $security === 'true' || $security === 1 || $security === true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        $httpPass = \json_encode([
            'data' => OpenSSL::encrypt($httpPass, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => \bin2hex($iv),
            'tag' => \bin2hex($tag),
            'version' => '1',
        ]);

        $task
            ->setAttribute('name', $name)
            ->setAttribute('status', $status)
            ->setAttribute('schedule', $schedule)
            ->setAttribute('updated', \time())
            ->setAttribute('next', $next)
            ->setAttribute('security', (int) $security)
            ->setAttribute('httpMethod', $httpMethod)
            ->setAttribute('httpUrl', $httpUrl)
            ->setAttribute('httpHeaders', $httpHeaders)
            ->setAttribute('httpUser', $httpUser)
            ->setAttribute('httpPass', $httpPass)
        ;

        if (false === $consoleDB->updateDocument($task->getArrayCopy())) {
            throw new Exception('Failed saving tasks to DB', 500);
        }

        if ($next) {
            ResqueScheduler::enqueueAt($next, 'v1-tasks', 'TasksV1', $task->getArrayCopy());
        }

        $response->json($task->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId/tasks/:taskId')
    ->desc('Delete Task')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'deleteTask')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('taskId', null, function () { return new UID(); }, 'Task unique ID.')
    ->action(function ($projectId, $taskId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $task = $project->search('$id', $taskId, $project->getAttribute('tasks', []));

        if (empty($task) || !$task instanceof Document) {
            throw new Exception('Task not found', 404);
        }

        if (!$consoleDB->deleteDocument($task->getId())) {
            throw new Exception('Failed to remove tasks from DB', 500);
        }

        $response->noContent();
    }, ['response', 'consoleDB']);

// Platforms

App::post('/v1/projects/:projectId/platforms')
    ->desc('Create Platform')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'createPlatform')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('type', null, function () { return new WhiteList(['web', 'flutter-ios', 'flutter-android', 'ios', 'android', 'unity']); }, 'Platform type.')
    ->param('name', null, function () { return new Text(256); }, 'Platform name.')
    ->param('key', '', function () { return new Text(256); }, 'Package name for android or bundle ID for iOS.', true)
    ->param('store', '', function () { return new Text(256); }, 'App store or Google Play store ID.', true)
    ->param('hostname', '', function () { return new Text(256); }, 'Platform client hostname.', true)
    ->action(function ($projectId, $type, $name, $key, $store, $hostname, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $platform = $consoleDB->createDocument([
            '$collection' => Database::SYSTEM_COLLECTION_PLATFORMS,
            '$permissions' => [
                'read' => ['team:'.$project->getAttribute('teamId', null)],
                'write' => ['team:'.$project->getAttribute('teamId', null).'/owner', 'team:'.$project->getAttribute('teamId', null).'/developer'],
            ],
            'type' => $type,
            'name' => $name,
            'key' => $key,
            'store' => $store,
            'hostname' => $hostname,
            'dateCreated' => \time(),
            'dateUpdated' => \time(),
        ]);

        if (false === $platform) {
            throw new Exception('Failed saving platform to DB', 500);
        }

        $project->setAttribute('platforms', $platform, Document::SET_TYPE_APPEND);

        $project = $consoleDB->updateDocument($project->getArrayCopy());

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($platform->getArrayCopy())
        ;
    }, ['response', 'consoleDB']);
    
App::get('/v1/projects/:projectId/platforms')
    ->desc('List Platforms')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'listPlatforms')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $platforms = $project->getAttribute('platforms', []);

        $response->json($platforms);
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/platforms/:platformId')
    ->desc('Get Platform')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getPlatform')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('platformId', null, function () { return new UID(); }, 'Platform unique ID.')
    ->action(function ($projectId, $platformId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $platform = $project->search('$id', $platformId, $project->getAttribute('platforms', []));

        if (empty($platform) || !$platform instanceof Document) {
            throw new Exception('Platform not found', 404);
        }

        $response->json($platform->getArrayCopy());
    }, ['response', 'consoleDB']);

App::put('/v1/projects/:projectId/platforms/:platformId')
    ->desc('Update Platform')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updatePlatform')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('platformId', null, function () { return new UID(); }, 'Platform unique ID.')
    ->param('name', null, function () { return new Text(256); }, 'Platform name.')
    ->param('key', '', function () { return new Text(256); }, 'Package name for android or bundle ID for iOS.', true)
    ->param('store', '', function () { return new Text(256); }, 'App store or Google Play store ID.', true)
    ->param('hostname', '', function () { return new Text(256); }, 'Platform client URL.', true)
    ->action(function ($projectId, $platformId, $name, $key, $store, $hostname, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $platform = $project->search('$id', $platformId, $project->getAttribute('platforms', []));

        if (empty($platform) || !$platform instanceof Document) {
            throw new Exception('Platform not found', 404);
        }

        $platform
            ->setAttribute('name', $name)
            ->setAttribute('dateUpdated', \time())
            ->setAttribute('key', $key)
            ->setAttribute('store', $store)
            ->setAttribute('hostname', $hostname)
        ;

        if (false === $consoleDB->updateDocument($platform->getArrayCopy())) {
            throw new Exception('Failed saving platform to DB', 500);
        }

        $response->json($platform->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId/platforms/:platformId')
    ->desc('Delete Platform')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'deletePlatform')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('platformId', null, function () { return new UID(); }, 'Platform unique ID.')
    ->action(function ($projectId, $platformId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $platform = $project->search('$id', $platformId, $project->getAttribute('platforms', []));

        if (empty($platform) || !$platform instanceof Document) {
            throw new Exception('Platform not found', 404);
        }

        if (!$consoleDB->deleteDocument($platform->getId())) {
            throw new Exception('Failed to remove platform from DB', 500);
        }

        $response->noContent();
    }, ['response', 'consoleDB']);

// Domains

App::post('/v1/projects/:projectId/domains')
    ->desc('Create Domain')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'createDomain')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('domain', null, function () { return new DomainValidator(); }, 'Domain name.')
    ->action(function ($projectId, $domain, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $document = $project->search('domain', $domain, $project->getAttribute('domains', []));

        if (!empty($document)) {
            throw new Exception('Domain already exists', 409);
        }

        $target = new Domain(App::getEnv('_APP_DOMAIN_TARGET', ''));

        if (!$target->isKnown() || $target->isTest()) {
            throw new Exception('Unreachable CNAME target ('.$target->get().'), plesse use a domain with a public suffix.', 500);
        }

        $domain = new Domain($domain);

        $domain = $consoleDB->createDocument([
            '$collection' => Database::SYSTEM_COLLECTION_DOMAINS,
            '$permissions' => [
                'read' => ['team:'.$project->getAttribute('teamId', null)],
                'write' => ['team:'.$project->getAttribute('teamId', null).'/owner', 'team:'.$project->getAttribute('teamId', null).'/developer'],
            ],
            'updated' => \time(),
            'domain' => $domain->get(),
            'tld' => $domain->getSuffix(),
            'registerable' => $domain->getRegisterable(),
            'verification' => false,
            'certificateId' => null,
        ]);

        if (false === $domain) {
            throw new Exception('Failed saving domain to DB', 500);
        }

        $project->setAttribute('domains', $domain, Document::SET_TYPE_APPEND);

        $project = $consoleDB->updateDocument($project->getArrayCopy());

        if (false === $project) {
            throw new Exception('Failed saving project to DB', 500);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->json($domain->getArrayCopy())
        ;
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/domains')
    ->desc('List Domains')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'listDomains')
    ->param('projectId', '', function () { return new UID(); }, 'Project unique ID.')
    ->action(function ($projectId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $domains = $project->getAttribute('domains', []);

        $response->json($domains);
    }, ['response', 'consoleDB']);

App::get('/v1/projects/:projectId/domains/:domainId')
    ->desc('Get Domain')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.read')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'getDomain')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('domainId', null, function () { return new UID(); }, 'Domain unique ID.')
    ->action(function ($projectId, $domainId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $domain = $project->search('$id', $domainId, $project->getAttribute('domains', []));

        if (empty($domain) || !$domain instanceof Document) {
            throw new Exception('Domain not found', 404);
        }

        $response->json($domain->getArrayCopy());
    }, ['response', 'consoleDB']);

App::patch('/v1/projects/:projectId/domains/:domainId/verification')
    ->desc('Update Domain Verification Status')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'updateDomainVerification')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('domainId', null, function () { return new UID(); }, 'Domain unique ID.')
    ->action(function ($projectId, $domainId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $domain = $project->search('$id', $domainId, $project->getAttribute('domains', []));

        if (empty($domain) || !$domain instanceof Document) {
            throw new Exception('Domain not found', 404);
        }

        $target = new Domain(App::getEnv('_APP_DOMAIN_TARGET', ''));

        if (!$target->isKnown() || $target->isTest()) {
            throw new Exception('Unreachable CNAME target ('.$target->get().'), plesse use a domain with a public suffix.', 500);
        }

        if ($domain->getAttribute('verification') === true) {
            return $response->json($domain->getArrayCopy());
        }

        // Verify Domain with DNS records
        $validator = new CNAME($target->get());

        if (!$validator->isValid($domain->getAttribute('domain', ''))) {
            throw new Exception('Failed to verify domain', 401);
        }

        $domain
            ->setAttribute('verification', true)
        ;

        if (false === $consoleDB->updateDocument($domain->getArrayCopy())) {
            throw new Exception('Failed saving domains to DB', 500);
        }

        // Issue a TLS certificate when domain is verified
        Resque::enqueue('v1-certificates', 'CertificatesV1', [
            'document' => $domain->getArrayCopy(),
            'domain' => $domain->getAttribute('domain'),
        ]);

        $response->json($domain->getArrayCopy());
    }, ['response', 'consoleDB']);

App::delete('/v1/projects/:projectId/domains/:domainId')
    ->desc('Delete Domain')
    ->groups(['api', 'projects'])
    ->label('scope', 'projects.write')
    ->label('sdk.namespace', 'projects')
    ->label('sdk.method', 'deleteDomain')
    ->param('projectId', null, function () { return new UID(); }, 'Project unique ID.')
    ->param('domainId', null, function () { return new UID(); }, 'Domain unique ID.')
    ->action(function ($projectId, $domainId, $response, $consoleDB) {
        /** @var Utopia\Response $response */
        /** @var Appwrite\Database\Database $consoleDB */

        $project = $consoleDB->getDocument($projectId);

        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS != $project->getCollection()) {
            throw new Exception('Project not found', 404);
        }

        $domain = $project->search('$id', $domainId, $project->getAttribute('domains', []));

        if (empty($domain) || !$domain instanceof Document) {
            throw new Exception('Domain not found', 404);
        }

        if (!$consoleDB->deleteDocument($domain->getId())) {
            throw new Exception('Failed to remove domains from DB', 500);
        }

        $response->noContent();
    }, ['response', 'consoleDB']);