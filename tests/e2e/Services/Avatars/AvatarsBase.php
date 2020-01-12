<?php

namespace Tests\E2E\Services\Avatars;

use Tests\E2E\Client;

trait AvatarsBase
{
    public function testGetCreditCard():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/credit-cards/visa', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/credit-cards/visa', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 200,
            'height' => 200,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/credit-cards/visa', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        /**
         * Test for FAILURE
         */

        $response = $this->client->call(Client::METHOD_GET, '/avatars/credit-cards/unknown', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);
       
        $response = $this->client->call(Client::METHOD_GET, '/avatars/credit-cards/visa', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 2001,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);
       
        return [];
    }

    public function testGetBrowser():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/browsers/ch', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/browsers/ch', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 200,
            'height' => 200,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/browsers/ch', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);
        
        /**
         * Test for FAILURE
         */

        $response = $this->client->call(Client::METHOD_GET, '/avatars/browsers/unknown', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);
        
        $response = $this->client->call(Client::METHOD_GET, '/avatars/browsers/ch', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 2001,
            'height' => 300,
            'quality' => 30,
        ]);
            
        $this->assertEquals(400, $response['headers']['status-code']);

        return [];
    }

    public function testGetFlag():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/flags/us', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/flags/us', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 200,
            'height' => 200,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/flags/us', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);
        
        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/flags/unknown', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);
        
        $response = $this->client->call(Client::METHOD_GET, '/avatars/flags/us', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'width' => 2001,
            'height' => 300,
            'quality' => 30,
        ]);
            
        $this->assertEquals(400, $response['headers']['status-code']);

        return [];
    }

    public function testGetImage():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/image', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/images/apple.png',
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/image', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/images/apple.png',
            'width' => 200,
            'height' => 200,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/image', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/images/apple.png',
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);
        
        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/image', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/images/unknown.png',
            'width' => 300,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(404, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/image', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/images/apple.png',
            'width' => 2001,
            'height' => 300,
            'quality' => 30,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        // TODO Add test for non-image file (PDF, WORD)

        return [];
    }

    public function testGetFavicon():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/favicon', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'https://appwrite.io/',
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        // $response = $this->client->call(Client::METHOD_GET, '/avatars/favicon', [
        //     'x-appwrite-project' => $this->getProject()['$uid'],
        // ], [
        //     'url' => 'https://www.bbc.com/',
        // ]);

        // $this->assertEquals(200, $response['headers']['status-code']);
        // $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        // $this->assertNotEmpty($response['body']);

        // $response = $this->client->call(Client::METHOD_GET, '/avatars/favicon', [
        //     'x-appwrite-project' => $this->getProject()['$uid'],
        // ], [
        //     'url' => 'https://edition.cnn.com/',
        // ]);

        // $this->assertEquals(200, $response['headers']['status-code']);
        // $this->assertEquals('image/x-icon; charset=UTF-8', $response['headers']['content-type']);
        // $this->assertNotEmpty($response['body']);

        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/favicon', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'unknown-address',
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/favicon', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'url' => 'http://unknown-address.test',
        ]);

        $this->assertEquals(404, $response['headers']['status-code']);

        return [];
    }

    public function testGetQR():array
    {
        /**
         * Test for SUCCESS
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 200,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 200,
            'margin' => 10,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 200,
            'margin' => 10,
            'download' => 1,
        ]);

        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals('attachment; filename="qr.png"', $response['headers']['content-disposition']);
        $this->assertEquals('image/png; charset=UTF-8', $response['headers']['content-type']);
        $this->assertNotEmpty($response['body']);
        
        /**
         * Test for FAILURE
         */
        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 1001,
            'margin' => 10,
            'download' => 1,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 400,
            'margin' => 11,
            'download' => 1,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        $response = $this->client->call(Client::METHOD_GET, '/avatars/qr', [
            'x-appwrite-project' => $this->getProject()['$uid'],
        ], [
            'text' => 'url:https://appwrite.io/',
            'size' => 400,
            'margin' => 10,
            'download' => 2,
        ]);

        $this->assertEquals(400, $response['headers']['status-code']);

        return [];
    }
}