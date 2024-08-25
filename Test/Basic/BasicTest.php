<?php

namespace Test\Synthetic;

use PHPUnit\Framework\TestCase;
use RestService\Server;
use Test\Client\InternalClient;
use Test\Controller\MyRoutes;

class BasicTest extends TestCase
{
    public function testCustomUrl()
    {
        $restService = Server::create('/', new MyRoutes)
            ->setClient(InternalClient::class)
            ->collectRoutes();

        $response = $restService->simulateCall('/test/test', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": "test"
        }
        JSON, $response);

    }
}
