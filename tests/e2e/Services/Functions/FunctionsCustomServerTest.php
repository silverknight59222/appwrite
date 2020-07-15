<?php

namespace Tests\E2E\Services\Functions;

use CURLFile;
use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideServer;

class FunctionsConsoleServerTest extends Scope
{
    use FunctionsBase;
    use ProjectCustom;
    use SideServer;

    public function testCreate():array
    {
        /**
         * Test for SUCCESS
         */
        $response1 = $this->client->call(Client::METHOD_POST, '/functions', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'name' => 'Test',
            'env' => 'node-14',
            'vars' => [
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ],
            'events' => [
                'account.create',
                'account.delete',
            ],
            'schedule' => '* * * * *',
            'timeout' => 10,
        ]);

        $functionId = (isset($response1['body']['$id'])) ? $response1['body']['$id'] : '';

        $this->assertEquals(201, $response1['headers']['status-code']);
        $this->assertNotEmpty($response1['body']['$id']);
        $this->assertEquals('Test', $response1['body']['name']);
        $this->assertEquals('node-14', $response1['body']['env']);
        $this->assertIsInt($response1['body']['dateCreated']);
        $this->assertIsInt($response1['body']['dateUpdated']);
        $this->assertEquals('', $response1['body']['tag']);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], $response1['body']['vars']);
        $this->assertEquals([
            'account.create',
            'account.delete',
        ], $response1['body']['events']);
        $this->assertEquals('* * * * *', $response1['body']['schedule']);
        $this->assertEquals(10, $response1['body']['timeout']);
       
        /**
         * Test for FAILURE
         */

        return [
            'functionId' => $functionId,
        ];
    }

    /**
     * @depends testCreate
     */
    public function testList(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 200);
        $this->assertEquals($function['body']['sum'], 1);
        $this->assertIsArray($function['body']['functions']);
        $this->assertCount(1, $function['body']['functions']);
        $this->assertEquals($function['body']['functions'][0]['name'], 'Test');

        return $data;
    }

    /**
     * @depends testList
     */
    public function testGet(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/' . $data['functionId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 200);
        $this->assertEquals($function['body']['name'], 'Test');
               
        /**
         * Test for FAILURE
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/x', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 404);

        return $data;
    }

    /**
     * @depends testGet
     */
    public function testUpdate($data):array
    {
        /**
         * Test for SUCCESS
         */
        $response1 = $this->client->call(Client::METHOD_PUT, '/functions/'.$data['functionId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'name' => 'Test1',
            'vars' => [
                'key4' => 'value4',
                'key5' => 'value5',
                'key6' => 'value6',
            ],
            'events' => [
                'account.update.name',
                'account.update.email',
            ],
            'schedule' => '* * * * 1',
            'timeout' => 5,
        ]);

        $this->assertEquals(200, $response1['headers']['status-code']);
        $this->assertNotEmpty($response1['body']['$id']);
        $this->assertEquals('Test1', $response1['body']['name']);
        $this->assertIsInt($response1['body']['dateCreated']);
        $this->assertIsInt($response1['body']['dateUpdated']);
        $this->assertEquals('', $response1['body']['tag']);
        $this->assertEquals([
            'key4' => 'value4',
            'key5' => 'value5',
            'key6' => 'value6',
        ], $response1['body']['vars']);
        $this->assertEquals([
            'account.update.name',
            'account.update.email',
        ], $response1['body']['events']);
        $this->assertEquals('* * * * 1', $response1['body']['schedule']);
        $this->assertEquals(5, $response1['body']['timeout']);
       
        /**
         * Test for FAILURE
         */

        return $data;
    }

    /**
     * @depends testUpdate
     */
    public function testCreateTag($data):array
    {
        /**
         * Test for SUCCESS
         */
        $tag = $this->client->call(Client::METHOD_POST, '/functions/'.$data['functionId'].'/tags', array_merge([
            'content-type' => 'multipart/form-data',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'command' => 'node ./test.js',
            'code' => new CURLFile(realpath(__DIR__ . '/../../../resources/function.tar.gz'), 'application/x-gzip', 'function.tar.gz'),
        ]);

        $tagId = (isset($tag['body']['$id'])) ? $tag['body']['$id'] : '';

        $this->assertEquals(201, $tag['headers']['status-code']);
        $this->assertNotEmpty($tag['body']['$id']);
        $this->assertIsInt($tag['body']['dateCreated']);
        $this->assertEquals('node ./test.js', $tag['body']['command']);
        $this->assertStringStartsWith('/storage/functions/app-', $tag['body']['codePath']);
        $this->assertEquals(521, $tag['body']['codeSize']);
       
        /**
         * Test for FAILURE
         */

        return array_merge($data, ['tagId' => $tagId]);
    }

    /**
     * @depends testCreateTag
     */
    public function testUpdateTag($data):array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/functions/'.$data['functionId'].'/tag', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'tag' => $data['tagId'],
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertNotEmpty($response['body']['$id']);
        $this->assertIsInt($response['body']['dateCreated']);
        $this->assertIsInt($response['body']['dateUpdated']);
        $this->assertEquals($data['tagId'], $response['body']['tag']);
       
        /**
         * Test for FAILURE
         */

        return $data;
    }

    /**
     * @depends testCreateTag
     */
    public function testListTags(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/tags', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 200);
        $this->assertEquals($function['body']['sum'], 1);
        $this->assertIsArray($function['body']['tags']);
        $this->assertCount(1, $function['body']['tags']);

        return $data;
    }

    /**
     * @depends testCreateTag
     */
    public function testGetTag(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/tags/' . $data['tagId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $function['headers']['status-code']);
        $this->assertEquals(521, $function['body']['codeSize']);

        /**
         * Test for FAILURE
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/tags/x', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 404);

        return $data;
    }


    /**
     * @depends testUpdateTag
     */
    public function testCreateExecution($data):array
    {
        /**
         * Test for SUCCESS
         */
        $execution = $this->client->call(Client::METHOD_POST, '/functions/'.$data['functionId'].'/executions', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'async' => 1,
        ]);

        $executionId = (isset($execution['body']['$id'])) ? $execution['body']['$id'] : '';

        $this->assertEquals(201, $execution['headers']['status-code']);
        $this->assertNotEmpty($execution['body']['$id']);
        $this->assertNotEmpty($execution['body']['functionId']);
        $this->assertIsInt($execution['body']['dateCreated']);
        $this->assertEquals($data['functionId'], $execution['body']['functionId']);
        $this->assertEquals('waiting', $execution['body']['status']);
        $this->assertEquals(0, $execution['body']['exitCode']);
        $this->assertEquals('', $execution['body']['stdout']);
        $this->assertEquals('', $execution['body']['stderr']);
        $this->assertEquals(0, $execution['body']['time']);
       
        /**
         * Test for FAILURE
         */

        return array_merge($data, ['executionId' => $executionId]);
    }


    /**
     * @depends testCreateExecution
     */
    public function testListExecutions(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/executions', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 200);
        $this->assertEquals($function['body']['sum'], 1);
        $this->assertIsArray($function['body']['executions']);
        $this->assertCount(1, $function['body']['executions']);
        $this->assertEquals($function['body']['executions'][0]['$id'], $data['executionId']);

        return $data;
    }

    /**
     * @depends testListExecutions
     */
    public function testGetExecution(array $data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/executions/' . $data['executionId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 200);
        $this->assertEquals($function['body']['$id'], $data['executionId']);

        /**
         * Test for FAILURE
         */
        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/executions/x', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals($function['headers']['status-code'], 404);

        return $data;
    }

    /**
     * @depends testGetExecution
     */
    public function testDeleteTag($data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_DELETE, '/functions/'.$data['functionId'].'/tags/' . $data['tagId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(204, $function['headers']['status-code']);
        $this->assertEmpty($function['body']);

        $function = $this->client->call(Client::METHOD_GET, '/functions/'.$data['functionId'].'/tags/' . $data['tagId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));
       
        $this->assertEquals(404, $function['headers']['status-code']);

        /**
         * Test for FAILURE
         */

        return $data;
    }

    /**
     * @depends testCreateTag
     */
    public function testDelete($data):array
    {
        /**
         * Test for SUCCESS
         */
        $function = $this->client->call(Client::METHOD_DELETE, '/functions/'.$data['functionId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(204, $function['headers']['status-code']);
        $this->assertEmpty($function['body']);

        $function = $this->client->call(Client::METHOD_GET, '/functions/' . $data['functionId'], array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));
       
        $this->assertEquals(404, $function['headers']['status-code']);

        /**
         * Test for FAILURE
         */

        return $data;
    }
}