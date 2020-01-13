<?php

namespace Tests\E2E\Services\Database;

use Tests\E2E\Client;

trait DatabaseBase
{
    public function testCreateCollection():array
    {
        /**
         * Test for SUCCESS
         */
        $actors = $this->client->call(Client::METHOD_POST, '/database', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'name' => 'Actors',
            'read' => ['*'],
            'write' => ['role:1', 'role:2'],
            'rules' => [
                [
                    'label' => 'First Name',
                    'key' => 'firstName',
                    'type' => 'text',
                    'default' => '',
                    'required' => true,
                    'array' => false
                ],
                [
                    'label' => 'Last Name',
                    'key' => 'lastName',
                    'type' => 'text',
                    'default' => '',
                    'required' => true,
                    'array' => false
                ],
            ],
        ]);

        $this->assertEquals($actors['headers']['status-code'], 201);
        $this->assertEquals($actors['body']['$collection'], 0);
        $this->assertEquals($actors['body']['name'], 'Actors');
        $this->assertIsArray($actors['body']['$permissions']);
        $this->assertIsArray($actors['body']['$permissions']['read']);
        $this->assertIsArray($actors['body']['$permissions']['write']);
        $this->assertCount(1, $actors['body']['$permissions']['read']);
        $this->assertCount(2, $actors['body']['$permissions']['write']);

        $movies = $this->client->call(Client::METHOD_POST, '/database', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
            'x-appwrite-key' => $this->getProject()['apiKey']
        ]), [
            'name' => 'Movies',
            'read' => ['*'],
            'write' => ['role:1', 'role:2'],
            'rules' => [
                [
                    'label' => 'Name',
                    'key' => 'name',
                    'type' => 'text',
                    'default' => '',
                    'required' => true,
                    'array' => false
                ],
                [
                    'label' => 'Release Year',
                    'key' => 'releaseYear',
                    'type' => 'numeric',
                    'default' => 0,
                    'required' => false,
                    'array' => false
                ],
                [
                    'label' => 'Actors',
                    'key' => 'actors',
                    'type' => 'document',
                    'default' => [],
                    'required' => false,
                    'array' => true,
                    'list' => [$actors['body']['$uid']],
                ],
            ],
        ]);

        $this->assertEquals($movies['headers']['status-code'], 201);
        $this->assertEquals($movies['body']['$collection'], 0);
        $this->assertEquals($movies['body']['name'], 'Movies');
        $this->assertIsArray($movies['body']['$permissions']);
        $this->assertIsArray($movies['body']['$permissions']['read']);
        $this->assertIsArray($movies['body']['$permissions']['write']);
        $this->assertCount(1, $movies['body']['$permissions']['read']);
        $this->assertCount(2, $movies['body']['$permissions']['write']);

        return array_merge(['moviesId' => $movies['body']['$uid'], 'actorsId' => $actors['body']['$uid']]);
    }

    /**
     * @depends testCreateCollection
     */
    public function testCreateDocument(array $data):array
    {
        $document1 = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Captain America',
                'releaseYear' => 1944,
                'actors' => [
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Chris',
                        'lastName' => 'Evans',
                    ],
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Samuel',
                        'lastName' => 'Jackson',
                    ],
                ]
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $document2 = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Spider-Man: Far From Home',
                'releaseYear' => 2019,
                'actors' => [
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Tom',
                        'lastName' => 'Holland',
                    ],
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Zendaya',
                        'lastName' => 'Maree Stoermer',
                    ],
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Samuel',
                        'lastName' => 'Jackson',
                    ],
                ]
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $document3 = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Spider-Man: Homecoming',
                'releaseYear' => 2017,
                'actors' => [
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Tom',
                        'lastName' => 'Holland',
                    ],
                    [
                        '$collection' => $data['actorsId'],
                        '$permissions' => ['read' => [], 'write' => []],
                        'firstName' => 'Zendaya',
                        'lastName' => 'Maree Stoermer',
                    ],
                ],
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $document4 = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'releaseYear' => 2020, // Missing title, expect an 400 error
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $this->assertEquals($document1['headers']['status-code'], 201);
        $this->assertEquals($document1['body']['$collection'], $data['moviesId']);
        $this->assertEquals($document1['body']['name'], 'Captain America');
        $this->assertEquals($document1['body']['releaseYear'], 1944);
        $this->assertIsArray($document1['body']['$permissions']);
        $this->assertIsArray($document1['body']['$permissions']['read']);
        $this->assertIsArray($document1['body']['$permissions']['write']);
        $this->assertCount(1, $document1['body']['$permissions']['read']);
        $this->assertCount(1, $document1['body']['$permissions']['write']);
        $this->assertCount(2, $document1['body']['actors']);

        $this->assertEquals($document2['headers']['status-code'], 201);
        $this->assertEquals($document2['body']['$collection'], $data['moviesId']);
        $this->assertEquals($document2['body']['name'], 'Spider-Man: Far From Home');
        $this->assertEquals($document2['body']['releaseYear'], 2019);
        $this->assertIsArray($document2['body']['$permissions']);
        $this->assertIsArray($document2['body']['$permissions']['read']);
        $this->assertIsArray($document2['body']['$permissions']['write']);
        $this->assertCount(1, $document2['body']['$permissions']['read']);
        $this->assertCount(1, $document2['body']['$permissions']['write']);
        $this->assertCount(3, $document2['body']['actors']);
        $this->assertEquals($document2['body']['actors'][0]['firstName'], 'Tom');
        $this->assertEquals($document2['body']['actors'][0]['lastName'], 'Holland');
        $this->assertEquals($document2['body']['actors'][1]['firstName'], 'Zendaya');
        $this->assertEquals($document2['body']['actors'][1]['lastName'], 'Maree Stoermer');
        $this->assertEquals($document2['body']['actors'][2]['firstName'], 'Samuel');
        $this->assertEquals($document2['body']['actors'][2]['lastName'], 'Jackson');

        $this->assertEquals($document3['headers']['status-code'], 201);
        $this->assertEquals($document3['body']['$collection'], $data['moviesId']);
        $this->assertEquals($document3['body']['name'], 'Spider-Man: Homecoming');
        $this->assertEquals($document3['body']['releaseYear'], 2017);
        $this->assertIsArray($document3['body']['$permissions']);
        $this->assertIsArray($document3['body']['$permissions']['read']);
        $this->assertIsArray($document3['body']['$permissions']['write']);
        $this->assertCount(1, $document3['body']['$permissions']['read']);
        $this->assertCount(1, $document3['body']['$permissions']['write']);
        $this->assertCount(2, $document3['body']['actors']);
        $this->assertEquals($document3['body']['actors'][0]['firstName'], 'Tom');
        $this->assertEquals($document3['body']['actors'][0]['lastName'], 'Holland');
        $this->assertEquals($document3['body']['actors'][1]['firstName'], 'Zendaya');
        $this->assertEquals($document3['body']['actors'][1]['lastName'], 'Maree Stoermer');

        $this->assertEquals($document4['headers']['status-code'], 400);

        return $data;
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocuments(array $data):array
    {
        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'order-field' => 'releaseYear',
            'order-type' => 'ASC',
            'order-cast' => 'int',
        ]);

        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][2]['releaseYear']);
        $this->assertCount(3, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'order-field' => 'releaseYear',
            'order-type' => 'DESC',
            'order-cast' => 'int',
        ]);

        $this->assertEquals(1944, $documents['body']['documents'][2]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(3, $documents['body']['documents']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsLimitAndOffset(array $data):array
    {
        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'limit' => 1,
            'order-field' => 'releaseYear',
            'order-type' => 'ASC',
            'order-cast' => 'int',
        ]);

        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'limit' => 2,
            'offset' => 1,
            'order-field' => 'releaseYear',
            'order-type' => 'ASC',
            'order-cast' => 'int',
        ]);

        $this->assertEquals(2017, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2019, $documents['body']['documents'][1]['releaseYear']);
        $this->assertCount(2, $documents['body']['documents']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsFirstAndLast(array $data):array
    {
        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'limit' => 1,
            'order-field' => 'releaseYear',
            'order-type' => 'ASC',
            'order-cast' => 'int',
            'first' => true,
        ]);

        $this->assertEquals(1944, $documents['body']['releaseYear']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'limit' => 2,
            'offset' => 1,
            'order-field' => 'releaseYear',
            'order-type' => 'ASC',
            'order-cast' => 'int',
            'last' => true,
        ]);

        $this->assertEquals(2019, $documents['body']['releaseYear']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testDocumentsListSuccessSearch(array $data):array
    {
        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'search' => 'Captain America',
        ]);

        $this->assertEquals(1944, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'search' => 'Homecoming',
        ]);

        $this->assertEquals(2017, $documents['body']['documents'][0]['releaseYear']);
        $this->assertCount(1, $documents['body']['documents']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'search' => 'spider',
        ]);

        $this->assertEquals(2019, $documents['body']['documents'][0]['releaseYear']);
        $this->assertEquals(2017, $documents['body']['documents'][1]['releaseYear']);
        $this->assertCount(2, $documents['body']['documents']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testListDocumentsFilters(array $data):array
    {
        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'filters' => [
                'actors.firstName=Tom'
            ],
        ]);

        $this->assertCount(2, $documents['body']['documents']);
        $this->assertEquals('Spider-Man: Far From Home', $documents['body']['documents'][0]['name']);
        $this->assertEquals('Spider-Man: Homecoming', $documents['body']['documents'][1]['name']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'filters' => [
                'releaseYear=1944'
            ],
        ]);

        $this->assertCount(1, $documents['body']['documents']);
        $this->assertEquals('Captain America', $documents['body']['documents'][0]['name']);

        $documents = $this->client->call(Client::METHOD_GET, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'filters' => [
                'releaseYear!=1944'
            ],
        ]);

        $this->assertCount(2, $documents['body']['documents']);
        $this->assertEquals('Spider-Man: Far From Home', $documents['body']['documents'][0]['name']);
        $this->assertEquals('Spider-Man: Homecoming', $documents['body']['documents'][1]['name']);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testUpdateDocument(array $data):array
    {
        $document = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Thor: Ragnaroc',
                'releaseYear' => 2017,
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $id = $document['body']['$uid'];
        $collection = $document['body']['$collection'];

        $this->assertEquals($document['headers']['status-code'], 201);
        $this->assertEquals($document['body']['name'], 'Thor: Ragnaroc');
        $this->assertEquals($document['body']['releaseYear'], 2017);

        $document = $this->client->call(Client::METHOD_PATCH, '/database/' . $collection . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Thor: Ragnarok'
            ]
        ]);

        $this->assertEquals($document['headers']['status-code'], 200);
        $this->assertEquals($document['body']['name'], 'Thor: Ragnarok');
        $this->assertEquals($document['body']['releaseYear'], 2017);

        $document = $this->client->call(Client::METHOD_GET, '/database/' . $collection . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()));

        $id = $document['body']['$uid'];
        $collection = $document['body']['$collection'];

        $this->assertEquals($document['headers']['status-code'], 200);
        $this->assertEquals($document['body']['name'], 'Thor: Ragnarok');
        $this->assertEquals($document['body']['releaseYear'], 2017);

        return [];
    }

    /**
     * @depends testCreateDocument
     */
    public function testDeleteDocument(array $data):array
    {
        $document = $this->client->call(Client::METHOD_POST, '/database/' . $data['moviesId'] . '/documents', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()), [
            'data' => [
                'name' => 'Thor: Ragnarok',
                'releaseYear' => 2017,
            ],
            'read' => ['user:'.$this->getUser()['$uid']],
            'write' => ['user:'.$this->getUser()['$uid']],
        ]);

        $id = $document['body']['$uid'];
        $collection = $document['body']['$collection'];

        $this->assertEquals($document['headers']['status-code'], 201);

        $document = $this->client->call(Client::METHOD_GET, '/database/' . $collection . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()));

        $this->assertEquals($document['headers']['status-code'], 200);

        $document = $this->client->call(Client::METHOD_DELETE, '/database/' . $collection . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()));

        $this->assertEquals($document['headers']['status-code'], 204);

        $document = $this->client->call(Client::METHOD_GET, '/database/' . $collection . '/documents/' . $id, array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], $this->getHeaders()));

        $this->assertEquals($document['headers']['status-code'], 404);
        
        return [];
    }
}