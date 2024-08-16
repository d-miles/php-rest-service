<?php

namespace Test\Synthetic;

use PHPUnit\Framework\TestCase;
use RestService\Server;
use Test\Client\InternalClient;

class RouteTest extends TestCase
{
    
    public function testAllRoutesClosures()
    {
        
        $restService = Server::create('/')
            ->setClient(InternalClient::class)
            ->addGetRoute('test', function(){
                return 'getTest';
            })
            ->addPostRoute('test', function(){
                return 'postTest';
            })
            ->addPatchRoute('test', function(){
                return 'patchTest';
            })
            ->addPutRoute('test', function(){
                return 'putTest';
            })
            ->addOptionsRoute('test', function(){
                return 'optionsTest';
            })
            ->addDeleteRoute('test', function(){
                return 'deleteTest';
            })
            ->addHeadRoute('test', function(){
                return 'headTest';
            })
            ->addRoute('all-test', function(){
                return 'allTest';
            });
        
        foreach ($restService->getClient()->methods as $method) {
            $this->assertEquals(<<<JSON
            {
                "status": 200,
                "data": "{$method}Test"
            }
            JSON, $restService->simulateCall('/test', $method));
            
            $this->assertEquals(<<<JSON
            {
                "status": 200,
                "data": "allTest"
            }
            JSON, $restService->simulateCall('/all-test', $method));
            
        }
        
    }
    
}
