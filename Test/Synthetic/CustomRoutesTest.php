<?php

namespace Test\Synthetic;

use PHPUnit\Framework\TestCase;
use RestService\Server;
use Test\Client\InternalClient;
use Test\Controller\MyRoutes;

class CustomRoutesTest extends TestCase
{
    
    public function testOwnController()
    {
        $restService = Server::create('/', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addPostRoute('login', 'postLogin');
        
        $response = $restService->simulateCall('/login?', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "MissingRequiredArgumentException",
            "message": "Argument 'username' is missing."
        }
        JSON, $response);
        
        $response = $restService->simulateCall('/login?username=bla', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "MissingRequiredArgumentException",
            "message": "Argument 'password' is missing."
        }
        JSON, $response);
        
        $response = $restService->simulateCall('/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
        
        $response = $restService->simulateCall('/login?username=peter&password=pwd', 'get');
        $this->assertEquals(<<<JSON
        {
            "status": 400,
            "error": "RouteNotFoundException",
            "message": "There is no route for 'login'."
        }
        JSON, $response);
    }
    
    public function testOwnControllerWithDifferentPrefix()
    {
        $restService = Server::create('/v1', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addPostRoute('login', 'postLogin');
        
        $response = $restService->simulateCall('/v1/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);

        $restService = Server::create('/v1/', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addPostRoute('login', 'postLogin');
        
        $response = $restService->simulateCall('/v1/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
        
        $restService = Server::create('v1', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addPostRoute('login', 'postLogin');

        $response = $restService->simulateCall('/v1/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
        
    }
    
    public function testSubController()
    {
        $restService = Server::create('v1', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addPostRoute('login', 'postLogin')
            ->addSubController('sub', new MyRoutes())
                ->addPostRoute('login', 'postLogin')
            ->done();
        
        $response = $restService->simulateCall('/v1/sub/login?username=peter&password=pwd', 'post');
        
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
    }
    
    public function testSubControllerWithSlashRootParent()
    {
        $restService = Server::create('/', new MyRoutes)
            ->setClient(InternalClient::class)
            ->addSubController('sub', new MyRoutes())
                ->addPostRoute('login', 'postLogin')
            ->done();
        
        $response = $restService->simulateCall('/sub/login?username=peter&password=pwd', 'post');
        $this->assertEquals(<<<JSON
        {
            "status": 200,
            "data": true
        }
        JSON, $response);
    }
    
}
