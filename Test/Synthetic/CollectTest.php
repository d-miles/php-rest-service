<?php

namespace Test\Synthetic;

use PHPUnit\Framework\TestCase;
use RestService\Server;
use Test\Client\InternalClient;
use Test\Controller\MyRoutes;

class CollectTest extends TestCase
{
    /**
     * @var Server
     */
    private readonly Server $restService;
    
    public function setUp() : void
    {
        $this->restService = Server::create('/', new MyRoutes)
            ->setClient(InternalClient::class)
            ->collectRoutes();
    }
    
    public function testNonPhpDocMethod()
    {
        $response = $this->restService->simulateCall('/method-without-php-doc', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": "hi"
        }
        JSON, $response);
    }
    
    public function testUrlAnnotation()
    {
        $response = $this->restService->simulateCall('/stats', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": "Stats for 1"
        }
        JSON, $response);
        
        $response = $this->restService->simulateCall('/stats/23', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": "Stats for 23"
        }
        JSON, $response);
        
    }

    public function testOwnController()
    {
        
        $response = $this->restService->simulateCall('/login', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "MissingRequiredArgumentException",
            "message": "Argument 'username' is missing."
        }
        JSON, $response);
        
        $response = $this->restService->simulateCall('/login?username=bla', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "MissingRequiredArgumentException",
            "message": "Argument 'password' is missing."
        }
        JSON, $response);
        
        $response = $this->restService->simulateCall('/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
        
        $response = $this->restService->simulateCall('/login?username=peter&password=pwd', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "RouteNotFoundException",
            "message": "There is no route for 'login'."
        }
        JSON, $response);
        
    }
    
}
