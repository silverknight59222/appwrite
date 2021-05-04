<?php

namespace Tests\E2E\Services\Teams;

use Tests\E2E\Client;

trait TeamsBaseClient
{
    /**
     * @depends testCreateTeam
     */
    public function testGetTeamMemberships($data):array
    {
        $teamUid = $data['teamUid'] ?? '';

        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/teams/'.$teamUid.'/memberships', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertIsInt($response['body']['sum']);
        $this->assertNotEmpty($response['body']['memberships'][0]['$id']);
        $this->assertEquals($this->getUser()['name'], $response['body']['memberships'][0]['name']);
        $this->assertEquals($this->getUser()['email'], $response['body']['memberships'][0]['email']);
        $this->assertEquals('owner', $response['body']['memberships'][0]['roles'][0]);

        /**
         * Test for FAILURE
         */

        return $data;
    }

    /**
     * @depends testCreateTeam
     */
    public function testCreateTeamMembership($data):array
    {
        $teamUid = $data['teamUid'] ?? '';
        $teamName = $data['teamName'] ?? '';
        $email = uniqid().'friend@localhost.test';
        $name = 'Friend User';

        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_POST, '/teams/'.$teamUid.'/memberships', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'email' => $email,
            'name' => $name,
            'roles' => ['admin', 'editor'],
            'url' => 'http://localhost:5000/join-us#title'
        ]);

        $this->assertEquals(201, $response['headers']['status-code']);
        $this->assertNotEmpty($response['body']['$id']);
        $this->assertNotEmpty($response['body']['userId']);
        $this->assertNotEmpty($response['body']['teamId']);
        $this->assertCount(2, $response['body']['roles']);
        $this->assertIsInt($response['body']['joined']);
        $this->assertEquals(false, $response['body']['confirm']);

        $lastEmail = $this->getLastEmail();

        $this->assertEquals($email, $lastEmail['to'][0]['address']);
        $this->assertEquals($name, $lastEmail['to'][0]['name']);
        $this->assertEquals('Invitation to '.$teamName.' Team at '.$this->getProject()['name'], $lastEmail['subject']);

        $secret = substr($lastEmail['text'], strpos($lastEmail['text'], '&secret=', 0) + 8, 256);
        $inviteUid = substr($lastEmail['text'], strpos($lastEmail['text'], '?inviteId=', 0) + 10, 13);
        $userUid = substr($lastEmail['text'], strpos($lastEmail['text'], '&userId=', 0) + 8, 13);

        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_POST, '/teams/'.$teamUid.'/memberships', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'email' => 'dasdkaskdjaskdjasjkd',
            'name' => $name,
            'roles' => ['admin', 'editor'],
            'url' => 'http://localhost:5000/join-us#title'
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_POST, '/teams/'.$teamUid.'/memberships', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'email' => $email,
            'name' => $name,
            'roles' => 'bad string',
            'url' => 'http://localhost:5000/join-us#title'
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_POST, '/teams/'.$teamUid.'/memberships', array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()), [
            'email' => $email,
            'name' => $name,
            'roles' => ['admin', 'editor'],
            'url' => 'http://example.com/join-us#title' // bad url
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        return [
            'teamUid' => $teamUid,
            'secret' => $secret,
            'inviteUid' => $inviteUid,
            'userUid' => $userUid,
            'email' => $email,
            'name' => $name
        ];
    }

    /**
     * @depends testCreateTeamMembership
     */
    public function testUpdateTeamMembership($data):array
    {
        $teamUid = $data['teamUid'] ?? '';
        $secret = $data['secret'] ?? '';
        $inviteUid = $data['inviteUid'] ?? '';
        $userUid = $data['userUid'] ?? '';
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';

        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/teams/'.$teamUid.'/memberships/'.$inviteUid.'/status', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ]), [
            'secret' => $secret,
            'userId' => $userUid,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertNotEmpty($response['body']['$id']);
        $this->assertNotEmpty($response['body']['userId']);
        $this->assertNotEmpty($response['body']['teamId']);
        $this->assertCount(2, $response['body']['roles']);
        $this->assertIsInt($response['body']['joined']);
        $this->assertEquals(true, $response['body']['confirm']);
        $session = $this->client->parseCookie((string)$response['headers']['set-cookie'])['a_session_'.$this->getProject()['$id']];

        /**
         * New User tries to update password without old password -> SHOULD PASS
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/account/password', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_'.$this->getProject()['$id'].'=' . $session,
        ]), [
            'password' => 'new-password'
        ]);

        $this->assertEquals($response['headers']['status-code'], 200);
        $this->assertIsArray($response['body']);
        $this->assertNotEmpty($response['body']);
        $this->assertNotEmpty($response['body']['$id']);
        $this->assertIsNumeric($response['body']['registration']);
        $this->assertEquals($response['body']['email'], $email);
        $this->assertEquals($response['body']['name'], $name);

        /**
         * New User again tries to update password with ONLY new password -> SHOULD FAIL
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/account/password', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_'.$this->getProject()['$id'].'=' . $session,
        ]), [
            'password' => 'new-password',
        ]);
        $this->assertEquals(401, $response['headers']['status-code']);

        /**
         * New User tries to update password by passing both old and new password -> SHOULD PASS
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/account/password', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
            'cookie' => 'a_session_'.$this->getProject()['$id'].'=' . $session,
        ]), [
            'password' => 'newer-password',
            'oldPassword' => 'new-password'
        ]);

        $this->assertEquals($response['headers']['status-code'], 200);
        $this->assertIsArray($response['body']);
        $this->assertNotEmpty($response['body']);
        $this->assertNotEmpty($response['body']['$id']);
        $this->assertIsNumeric($response['body']['registration']);
        $this->assertEquals($response['body']['email'], $email);
        $this->assertEquals($response['body']['name'], $name);

        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_PATCH, '/teams/'.$teamUid.'/memberships/'.$inviteUid.'/status', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ]), [
            'secret' => 'sdasdasd',
            'userId' => $userUid,
        ]);

        $this->assertEquals(401, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_PATCH, '/teams/'.$teamUid.'/memberships/'.$inviteUid.'/status', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ]), [
            'secret' => '',
            'userId' => $userUid,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_PATCH, '/teams/'.$teamUid.'/memberships/'.$inviteUid.'/status', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ]), [
            'secret' => $secret,
            'userId' => 'sdasd',
        ]);

        $this->assertEquals(401, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_PATCH, '/teams/'.$teamUid.'/memberships/'.$inviteUid.'/status', array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ]), [
            'secret' => $secret,
            'userId' => '',
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        return $data;
    }

    /**
     * @depends testUpdateTeamMembership
     */
    public function testDeleteTeamMembership($data):array
    {
        $teamUid = $data['teamUid'] ?? '';
        $inviteUid = $data['inviteUid'] ?? '';
        
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_DELETE, '/teams/'.$teamUid.'/memberships/'.$inviteUid, array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(204, $response['headers']['status-code']);
        $this->assertEmpty($response['body']);

        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_GET, '/teams/'.$teamUid.'/memberships/'.$inviteUid, array_merge([
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'x-appwrite-project' => $this->getProject()['$id'],
        ], $this->getHeaders()));

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertCount(1, $response['body']['memberships']);

        return [];
    }
}