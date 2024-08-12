<div align="center">

# PHP REST Service

PHP REST Service is a simple and fast PHP class for RESTful JSON APIs.

[![Build Status](https://img.shields.io/github/actions/workflow/status/d-miles/php-rest-service/php.yml?logo=php&logoColor=%23777BB4&logoSize=auto&labelColor=%23efefef)](https://github.com/d-miles/php-rest-service/actions/workflows/php.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/d-miles/php-rest-service?style=flat-square)](https://www.php.net/downloads)
[![Package Version](https://img.shields.io/packagist/v/d-miles/php-rest-service?style=flat-square)](https://packagist.org/packages/d-miles/php-rest-service)
[![License](https://img.shields.io/github/license/d-miles/php-rest-service?style=flat-square)](https://github.com/d-miles/php-rest-service/blob/master/LICENSE)

[https://d-miles.github.io/php-rest-service](https://d-miles.github.io/php-rest-service)
</div>

## Features

+ Easy to use syntax
+ Regular Expression support
+ Error handling through PHP Exceptions
+ JSON, XML, and plain text responses
+ Automatic OpenAPI specification generation
+ Parameter validation through PHP function signature
+ Can return a summary of all routes or one route through `OPTIONS` method based on PHPDoc (if `OPTIONS` is not overridden)
+ Support of `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD` and `OPTIONS`
+ Suppress the HTTP status code with ?_suppress_status_code=1 (for clients that have troubles with that)
+ Supports custom error handling, logging, access control and response formatting functions.
+ Supports ?_method=`httpMethod` as addition to the actual HTTP method.
+ With auto-generation through PHP's `reflection`


## Requirements
* PHP 8.1+ (Tested on PHP 8.1 - 8.4)
## Installation
`php composer require d-miles/php-rest-service`

## Demo

### Manual Endpoint Creation

```php
use RestService\Server;

Server::create('/')
  ->addGetRoute('test/(\D+)', function($param){
    return 'Yay!' . $param; // $param pulled from URL capture group
  })
  ->addPostRoute('foo', function($field1) {
    return 'Hello ' . $field1; // same as "return 'Hello ' . $_POST('field1');"
  })
  ->addGetRoute('use/this/name', function(){
      return 'Hi there';
  })
->run();

```

### Automatic Endpoint Creation

```php
namespace MyRestApi;

use RestService\Server;

class Admin {
  /*
   * @url /test/(\d+)
   */
  public function getTest($param) {
    return 'Yay!' . $param; // $param pulled from URL capture group
  }
  public function postFoo($field1){
    return 'Hello ' . $field1; // same as "return 'Hello ' . $_POST('field1');"
  }
  /*
   * @url /use/this/name
   */
  public function getNotThisName($field1){
    return 'Hi there';
  }
}

Server::create('/', 'myRestApi\Admin')
    ->collectRoutes()
->run();
```

Both methods will generate the following endpoints:
```
+ GET  /test/:param
+ POST /foo
+ GET  /use/this/name
```

# Documentation

Read the full documentation at [https://d-miles.github.io/php-rest-service](https://d-miles.github.io/php-rest-service).

## License

Licensed under the MIT License. See the LICENSE file for more details.
