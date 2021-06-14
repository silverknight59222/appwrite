<?php

namespace Appwrite\Tests;

use Appwrite\Database\Document;
use Appwrite\Realtime;
use PHPUnit\Framework\TestCase;

class RealtimeTest extends TestCase
{
    public $connections = [];
    public $subscriptions = [];

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testUser()
    {
        Realtime\Parser::setUser(new Document([
            '$id' => '123',
            'memberships' => [
                [
                    'teamId' => 'abc',
                    'roles' => [
                        'administrator',
                        'god'
                    ]
                ],
                [
                    'teamId' => 'def',
                    'roles' => [
                        'guest'
                    ]
                ]
            ]
        ]));

        $roles = Realtime\Parser::getRoles();

        $this->assertCount(7, $roles);
        $this->assertContains('user:123', $roles);
        $this->assertContains('role:member', $roles);
        $this->assertContains('team:abc', $roles);
        $this->assertContains('team:abc/administrator', $roles);
        $this->assertContains('team:abc/god', $roles);
        $this->assertContains('team:def', $roles);
        $this->assertContains('team:def/guest', $roles);

        $channels = [
            0 => 'files',
            1 => 'documents',
            2 => 'documents.789',
            3 => 'account',
            4 => 'account.456'
        ];

        $channels = Realtime\Parser::parseChannels($channels);

        $this->assertCount(4, $channels);
        $this->assertArrayHasKey('files', $channels);
        $this->assertArrayHasKey('documents', $channels);
        $this->assertArrayHasKey('documents.789', $channels);
        $this->assertArrayHasKey('account.123', $channels);
        $this->assertArrayNotHasKey('account', $channels);
        $this->assertArrayNotHasKey('account.456', $channels);

        Realtime\Parser::subscribe('1', 1, $roles, $this->subscriptions, $this->connections, $channels);

        $event = [
            'project' => '1',
            'permissions' => ['*'],
            'data' => [
                'channels' => [
                    0 => 'account.123',
                ]
            ]
        ];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['role:member'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['user:123'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['team:abc'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['team:abc/administrator'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['team:abc/god'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['team:def'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['team:def/guest'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['permissions'] = ['user:456'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertEmpty($receivers);

        $event['permissions'] = ['team:def/member'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertEmpty($receivers);

        $event['permissions'] = ['*'];
        $event['data']['channels'] = ['documents.123'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertEmpty($receivers);

        $event['data']['channels'] = ['documents.789'];

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertCount(1, $receivers);
        $this->assertEquals(1, $receivers[0]);

        $event['project'] = '2';

        $receivers = Realtime\Parser::identifyReceivers(
            $event, 
            $this->subscriptions
        );

        $this->assertEmpty($receivers);

        Realtime\Parser::unsubscribe(2, $this->subscriptions, $this->connections);

        $this->assertCount(1, $this->connections);
        $this->assertCount(7, $this->subscriptions['1']);


        Realtime\Parser::unsubscribe(1, $this->subscriptions, $this->connections);

        $this->assertEmpty($this->connections);
        $this->assertEmpty($this->subscriptions);
    }
}
