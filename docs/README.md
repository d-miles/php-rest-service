# PHP REST Service

PHP REST Service is a simple and fast PHP class for RESTful JSON APIs.

![Build Status](https://img.shields.io/circleci/build/github/cdgco/php-rest-service?style=flat-square)
![PHP Version](https://img.shields.io/packagist/php-v/cdgco/php-rest-service?style=flat-square)

## Features

+ Easy to use syntax
+ Regular Expression support
+ Error handling through PHP Exceptions
+ Parameter validation through PHP function signature
+ Can return a summary of all routes or one route through `OPTIONS` method based on PHPDoc (if `OPTIONS` is not overridden)
+ Support of `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD` and `OPTIONS`
+ Suppress the HTTP status code with `?_suppress_status_code=1` (for clients that have troubles with that)
+ Supports `?_method=<httpMethod>` as addition to the actual HTTP method.
+ With auto-generation through PHP's `reflection`

## Installation

### Composer

Run `php composer require cdgco/php-rest-service`, then include the Composer autoloader with `include 'vendor/autoload.php';`.

### Manual

Copy `Server.php` to your directory and include with `include 'Server.php';`.

## Web Server Configuration

PHP-REST-Service acts as a single page application, so all requests must be sent to the index.php file (or the file you want to serve).

For example, on apache, you can add the following lines to your `.htaccess` file:

```apacheconf
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.+) index.php/$1 [L,QSA]
```

## Basic Usage

### Manual Endpoint Creation

With manual endpoint creation, you must define a new route for each endpoint you want to expose.
This is done by using the `addGetRoute()`, `addPostRoute()`, `addPutRoute()`, `addDeleteRoute()`, `addPatchRoute()`, `addHeadRoute()`, `addOptionsRoute()` and `addRoute()` methods. 

All route methods accept a path and a callback (e.g. `addGetRoute('path', 'callbackFunction')`), while the `addRoute()` method also accepts the HTTP method (e.g. `addRoute('path', 'callbackFunction', 'get')`). If no method is specified, the default method is `_all_`.

Each of the aforementioned methods will add a route for the specified HTTP method, with the exception of `addRoute()` which will add a route for all HTTP methods.

```php
use RestService\Server;

Server::create('/')
  ->addGetRoute('test', function(){
    return 'Yay!';
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

With automatic endpoint creation, you can supply a PHP class that contains endpoint functions. The router will then scan the class for functions that start with `get`, `post`, `put`, `delete`, `patch`, `head` or `options` and add the corresponding route.

Function names will be converted from camel case to dashes,  so `getFooBar()` will be converted to `/foo-bar`.

You can also bind a function to a route other than the function name. Simply use the `@url` annotation to define the route.
For example `@url /foo` will bind the function to the route `/foo`.

```php
namespace MyRestApi;

use RestService\Server;

class Admin {
  public function getTest(){
    return 'Yay!';
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

Bot methods will generate the following endpoints:
```
+ GET  /test
+ POST /foo
+ GET  /use/this/name
```

## Responses

The response body is always a JSON object containing a status code and the actual data.
If a exception has been thrown, it contains the error code, the exception class name as error and the message as message.

Some examples:

```json
{
  "status": 200,
  "data": true
}
```
```json
{
  "status": 400,
  "error": "MissingRequiredArgumentException",
  "message": "Argument 'username' is missing"
}
```
```json
{
    "status": 500,
    "error": "InvalidLoginException",
    "message": "Login is invalid or no access"
}
```

For verbose error messages, enable debug mode on the desired controller by using the `->setDebugMode(true)` method:

```php
Server::create('/', 'myRestApi\Admin')
    ->setDebugMode(true)
    ->collectRoutes()
->run();
```

This will result in messages like:

```json
{
    "status": 500,
    "error": "InvalidLoginException",
    "message": "Login is invalid or no access",
    "line": 10,
    "file": "libs/RestAPI/Admin.class.php",
    "trace": <debugTrace>
}
```

## PHPDoc

For the full class-level documentation, please refer to the [PHPDoc](PHPDoc) documentation.

## License

Licensed under the MIT License. See the LICENSE file for more details.
