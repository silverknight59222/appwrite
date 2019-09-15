<?php

namespace Tests\E2E;

use Tests\E2E\Client;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client = null;
    protected $endpoint = 'http://localhost/v1';
    protected $demoEmail = '';
    protected $demoPassword = '';

    public function setUp()
    {
        $this->client = new Client();
    
        $this->client
            ->setEndpoint($this->endpoint)
        ;

        $this->demoEmail = 'user.' . rand(0,1000000) . '@appwrite.io';
        $this->demoPassword = 'password.' . rand(0,1000000);
    }

    public function tearDown()
    {
        $this->client = null;
    }

    public function testRegisterSuccess()
    {
        $response = $this->client->call(Client::METHOD_POST, '/auth/register', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
        ], [
            'email' => $this->demoEmail,
            'password' => $this->demoPassword,
            'redirect' => 'http://localhost/confirm',
            'success' => 'http://localhost/success',
            'failure' => 'http://localhost/failure',
            'name' => 'Demo User',
        ]);

        $this->assertEquals('http://localhost/success', $response['headers']['location']);
        $this->assertEquals("\n", $response['body']);

        return [
            'demoEmail' => $this->demoEmail,
            'demoPassword' => $this->demoPassword,
        ];
    }

    /**
     * @depends testRegisterSuccess
     */
    public function testLoginSuccess($data)
    {
        $response = $this->client->call(Client::METHOD_POST, '/auth/login', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
        ], [
            'email' => $data['demoEmail'],
            'password' => $data['demoPassword'],
            'success' => 'http://localhost/success',
            'failure' => 'http://localhost/failure',
        ]);

        $session = $this->client->parseCookie($response['headers']['set-cookie'])['a-session-console'];

        $this->assertEquals('http://localhost/success', $response['headers']['location']);
        $this->assertEquals("\n", $response['body']);

        return [
            'email' => $data['demoEmail'],
            'password' => $data['demoPassword'],
            'session' => $session
        ];
    }

    /**
     * @depends testLoginSuccess
     */
    public function testAccountSuccess($data)
    {
        $response = $this->client->call(Client::METHOD_GET, '/account', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'cookie' => 'a-session-console=' . $data['session'],
        ], []);

        $this->assertEquals('Demo User', $response['body']['name']);
        $this->assertEquals($data['email'], $response['body']['email']);
        $this->assertEquals(false, $response['body']['confirm']);
        $this->assertIsArray($response['body']['roles']);
        $this->assertIsInt($response['body']['registration']);
        $this->assertEquals('*', $response['body']['roles'][0]);
        $this->assertEquals('user:' . $response['body']['$uid'], $response['body']['roles'][1]);
        $this->assertEquals('role:1', $response['body']['roles'][2]);

        return $data;
    }

    /**
     * @depends testAccountSuccess
     */
    public function testLogoutSuccess($data)
    {
        $response = $this->client->call(Client::METHOD_DELETE, '/auth/logout', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'cookie' => 'a-session-console=' . $data['session'],
        ], []);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('success', $response['body']['result']);
    }

    public function testLogoutFailure()
    {
        $response = $this->client->call(Client::METHOD_DELETE, '/auth/logout', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
        ], []);

        $this->assertEquals('401', $response['body']['code']);
    }
}