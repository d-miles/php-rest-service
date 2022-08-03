<div align="center">

# PHP REST Service

PHP REST Service is a simple and fast PHP class for RESTful JSON APIs.

![Build Status](https://img.shields.io/circleci/build/github/cdgco/php-rest-service?style=flat-square)
![PHP Version](https://img.shields.io/packagist/php-v/cdgco/php-rest-service?style=flat-square)
![Package Version](https://img.shields.io/packagist/v/cdgco/php-rest-service?style=flat-square)
![License](https://img.shields.io/github/license/cdgco/php-rest-service?style=flat-square)

[https://cdgco.github.io/php-rest-service](https://cdgco.github.io/php-rest-service)
</div>

## Features

+ Easy to use syntax
+ Regular Expression support
+ Error handling through PHP Exceptions
+ Parameter validation through PHP function signature
+ Can return a summary of all routes or one route through `OPTIONS` method based on PHPDoc (if `OPTIONS` is not overridden)
+ Support of `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD` and `OPTIONS`
+ Suppress the HTTP status code with ?_suppress_status_code=1 (for clients that have troubles with that)
+ Supports ?_method=`httpMethod` as addition to the actual HTTP method.
+ With auto-generation through PHP's `reflection`


## Requirements
* PHP 7.4+
## Installation
`php composer require cdgco/php-rest-service`

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
  >addGetRoute('use/this/name', function(){
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
+ GET  /test
+ POST /foo
+ GET  /use/this/name
```

# Documentation

Read the full documentation at [https://cdgco.github.io/php-rest-service](https://cdgco.github.io/php-rest-service).

## License

Licensed under the MIT License. See the LICENSE file for more details.
