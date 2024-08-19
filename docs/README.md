<div align="center">

<!--lint ignore no-dead-urls-->
# PHP REST Service <!--CURRENT_VERSION--><!--END CURRENT_VERSION-->

PHP REST Service is a simple and fast PHP class for RESTful JSON APIs.

[![Build Status](https://img.shields.io/github/actions/workflow/status/d-miles/php-rest-service/php.yml?logo=php&logoColor=%23777BB4&logoSize=auto&labelColor=%23efefef)](https://github.com/d-miles/php-rest-service/actions/workflows/php.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/d-miles/php-rest-service?style=flat-square)](https://www.php.net/downloads)
[![Package Version](https://img.shields.io/packagist/v/d-miles/php-rest-service?style=flat-square)](https://packagist.org/packages/d-miles/php-rest-service)
[![License](https://img.shields.io/github/license/d-miles/php-rest-service?style=flat-square)](https://github.com/d-miles/php-rest-service/blob/master/LICENSE)
</div>

## Features

+ Easy to use syntax
+ Regular Expression routing support
+ Error handling using `Exception`
+ Support for JSON, XML, and plain-text responses by default
+ Automatic endpoint generation using reflection
+ Automatic parameter validation using function signature
+ Automatic OpenAPI specification generation
+ Custom access control
+ Custom exception handling
+ Custom response formatting
+ Support for all HTTP methods (`GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD`, and `OPTIONS`)
  + Suppress the HTTP status code with `?_suppress_status_code=1` (for clients that have troubles with that)
  + Override HTTP method using query string: `?_method={httpMethod}`
  + Summarize routes using the `OPTIONS` HTTP method

## Installation

### Requirements

PHP REST Service requires PHP <!--MIN_PHP_VERSION-->8.1<!--END MIN_PHP_VERSION-->+ and has been tested using PHP <!--TESTED_PHP_VERSIONS-->8.1, 8.2, and 8.3<!--END TESTED_PHP_VERSIONS-->. There are no dependecies.

### Composer

Run `php composer require d-miles/php-rest-service`, then include the Composer autoloader with `require 'vendor/autoload.php';`.

### Web Server Configuration

PHP REST Service acts as a single page application, so all requests must be redirected to the `index.php` file.

For example, if you are using Apache you can add the following lines to your `.htaccess` file to redirect all requests to `index.php`:

```apacheconf
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.+) index.php/$1 [L,QSA]
```

Nginx's default behaviour will do the same, meaning that there is no need for the same type of configuration when the file is named `index.php`.

## Basic Usage

<!-- panels:start -->
<!-- div:left-panel -->

### Manual Endpoint Creation
With manual endpoint creation, you must define a new route for each endpoint you want to expose using the following methods:

* <span style="font-family: monospace">GET&nbsp;&nbsp;&nbsp;&nbsp;</span> [`addGetRoute()`](phpdoc/#serveraddgetroute)
* <span style="font-family: monospace">POST&nbsp;&nbsp;&nbsp;</span> [`addPostRoute()`](phpdoc/#serveraddpostroute)
* <span style="font-family: monospace">PUT&nbsp;&nbsp;&nbsp;&nbsp;</span> [`addPutRoute()`](phpdoc/#serveraddputroute)
* <span style="font-family: monospace">DELETE&nbsp;</span> [`addDeleteRoute()`](phpdoc/#serveradddeleteroute)
* <span style="font-family: monospace">PATCH&nbsp;&nbsp;</span> [`addPatchRoute()`](phpdoc/#serveraddpatchroute)
* <span style="font-family: monospace">HEAD&nbsp;&nbsp;&nbsp;</span> [`addHeadRoute()`](phpdoc/#serveraddheadroute)
* <span style="font-family: monospace">OPTIONS</span> [`addOptionsRoute()`](phpdoc/#serveraddoptionsroute)

Each of these methods has the following signature:
```php
function (string $path, callable $callbackFn)
```

Alternatively, you could also use [`addRoute()`](phpdoc/#serveraddroute) to add a route for a specific HTTP method, or omit the `$method` argument altogether to add a route for _all_ HTTP methods:
```php
function addRoute(string $path, callable $callbackFn, string $method = '_all_')
```

### Automatic Endpoint Generation

With automatic endpoint generation, you can supply a PHP class that contains endpoint functions. By calling the [`collectRoutes()`](phpdoc/#servercollectroutes) method, the router will scan the class for functions that start with `get`, `post`, `put`, `delete`, `patch`, `head` or `options` and add the corresponding route.

Function names will be converted from camel-case to dash-case. eg. `getFooBar()` will be converted to `/foo-bar`.

You can also bind a function to a route other than the function name. Simply use the `@url` PHPDoc annotation to define the route.
For example, `@url /foo-bar` will bind the function to the same route as `getFooBar()`.

<!-- div:right-panel -->

#### Example

<!-- tabs:start -->

##### **Manual Endpoints**
```php
use RestService\Server;

Server::create('/')
    ->addGetRoute('test', function() {
        return 'Yay!';
    })
    ->addPostRoute('foo', function($field1) {
        // $field1 is the equivalent of $_POST['field1']
        return 'Hello ' . $field1;
    })
    ->addGetRoute('use/this/name', function() {
        return 'Hi there';
    })
    ->run();
```

##### **Automatic Endpoints**
```php
namespace MyRestApi;

use RestService\Server;

class Admin {
    public function getTest(){
        return 'Yay!';
    }
    
    public function postFoo($field1){
      // $field1 is the equivalent of $_POST['field1']
      return 'Hello ' . $field1;
    }
    
    /*
     * @url /use/this/name
     */
    public function getNotThisName($field1){
        return 'Hi there';
    }
}

Server::create('/', Admin::class)
    ->collectRoutes()
    ->run();
```

<!-- tabs:end -->

Both examples above generate the following endpoints:
```
+ GET  /test
+ POST /foo
+ GET  /use/this/name
```

<!-- panels:end -->

## Advanced Usage

### Regular Expressions

For more advanced routing, you can use a regular expression in the `$path` value. When using automatic endpoint generation, this must be done using the `@url` PHPDoc annotation.

?> **NOTE** The router wraps the `$path`/`@url` value with `^` (start-of-line match) and `$` (end-of-line match) to match the entire path, using the delimiter `|`.

<!-- panels:start -->

<!-- div:left-panel -->

#### Dynamic URLs

In order to match all routes that start with `/foo/`, you would do one of the following:

<!-- div:right-panel -->

<!-- tabs:start -->

###### **Manual Endpoints**
```php
->addGetRoute('foo/.*', fn() => 'bar')
```

###### **Automatic Endpoints**
```php
/**
 * @url foo/.*
 */
public function getFoo() {
    return 'bar';
}
```

<!-- tabs:end -->

<!-- panels:end -->

<!-- panels:start -->
<!-- div:left-panel -->

#### Capturing URL Parameters

To capture parameters from the URL for use as function arguments, simply include one or more capturing groups in the `$path`/`@url` value.

For example, in order to match the `:id` and `:action` parameters in the following URL, `/foo/:id/something/:action`, where `:id` is an integer and `:action` is a string, you can use the following:

<!-- div:right-panel -->

<!-- tabs:start -->

###### **Manual Endpoints**
```php
->addPostRoute('foo/(\d+)/something/(\w+)', 'postFoo')
```

###### **Automatic Endpoints**
```php
/**
 * @url foo/(\d+)/something/(\w+)
 */
public function postFoo($arg1, $arg2){
  return 'Yay!';
}
```

<!-- tabs:end -->

<!-- panels:end -->

The server will automatically bind the captured parameters to the first `n` arguments of the function, where `n` is the number of capture groups in the regular expression.

You can still use GET, POST, and PUT parameters in the endpoint, but they must be bound to variables after the capture groups.

For example, in order to add a POST parameter `content` to the endpoint, you can use the following:

```php
/**
 * @url foo/(\d+)/something/(\w+)
 */
public function postFoo($arg1, $arg2, $content){
  return 'Yay!';
}
```

### Response Formatting

#### Built-in Response Formatters

You can use the [`setFormat()`](phpdoc/#clientsetformat) method of the API client to specify the response format.
Available formats are `json`, `xml`, and `text`.

In order to set the format, you must first access the current client using the [`getClient()`](phpdoc/#servergetclient) method.
You may only set the format on the top level controller, as each sub controller shares the same client with the parent.

If you would like to have different formats for different endpoints, you must create a new server using the [`create()`](phpdoc/#servercreate) method, rather than creating a new controller.

```php
Server::create('test')
    ->getClient()
        ->setFormat('text')
    ->getController()
    ->addGetRoute('', function($test) {
        return "Hello {$test}";
    })
    ->run();
```

#### Custom Response Formatters

You can also define your own response formatters using the [`setCustomFormat()`](phpdoc/#clientsetcustomformat) method along with the [`setFormat()`](phpdoc/#clientsetformat) method. The formatting function will be called with a single argument, an associative array of the response data.

For example:
```
[
    'status' => 'success',
    'data' => 'Hello World!'
]
```

The formatter function should set the response header and return a string.

The following example shows an adapted implementation of the plain text formatter:

```php
Server::create('test')
    ->getClient()
        ->setCustomFormat(function($message) {
            if (php_sapi_name() !== 'cli' )
                header('Content-Type: text/plain; charset=utf-8');

            $text = '';
            foreach ($message as $key => $data) {
                $key = is_numeric($key) ? '' : "{$key}: ";
                $text .= "{$key}{$data}\n";
            }
            return $text;
        })
        ->setFormat('custom')
    ->getController()
    ->addGetRoute('', function($test) {
        return "Hello {$test}";
    })
    ->run();
```

### OpenAPI Specification

<!-- panels:start -->
<!-- div:left-panel -->

PHP REST Service can automatically generate an OpenAPI specification file for your API.

To enable OpenAPI generation, simply use the [`setApiSpec()`](phpdoc/#serversetapispec) method on the desired controller, passing the name, version, description (optional), base path (optional), additional information defined by the [`Info Object`](https://swagger.io/specification/#info-object) as part of the OpenAPI spec (optional), and whether or not to recursively include child controllers (optional).

The following example will generate an OpenAPI specification in JSON format, accessible at the `/openapi.json` GET endpoint of your API, including all endpoints in child controllers.

Request and response types and their descriptions will be pulled from the PHPDoc comments of your endpoint functions. If no comment annotations are detected, the request and response types will default to `AnyValue`.

?> **Note:** If you have already defined an endpoint named `/openapi.json`, it will take precedent over the internally-generated route.

<!-- div:right-panel -->

```php
Server::create('test')
    ->setApiSpec('My New API', '1.2.3',
                 description: 'This is my new API', 
                 server: 'https://example.com/api',
                 additionalInfo: [
                    'contact' => [
                        'name'  => 'John Doe',
                        'email' => 'john.doe@example.com'
                    ]
                 ]
                 recurse: true)
    ->addGetRoute('', function($test) {
        return "Hello {$test}";
    })
```

<!-- panels:end -->

#### Specifying a custom URL

<!-- panels:start -->
<!-- div:left-panel -->
If your route contains regular expressions with capturing groups, an attempt will be made to match the capturing group to the corresponding argument name; however, it is unable to parse argument names from regular expressions without capturing groups. In these instances, you could instead add the `@openapi-url` PHPDoc annotation to your endpoint function.

<!-- div:right-panel -->

```php
/**
 * @url /foo/\d+/(\w+)
 * @openapi-url /foo/{var1}/{var2}
 */
```

<!-- panels:end -->

### Access Control

<!-- panels:start -->
<!-- div:left-panel -->

In order to restrict access to endpoints, you can use the [`setCheckAccess()`](phpdoc/#serversetcheckaccess) method to supply an access control function. Access will be denied when the access control function throws an exception.

The access control function has the following method signature:

```php
function(string $url, string $route, string $method, array $args)
```

<!-- div:right-panel -->

To deny POST access to the `/foo` endpoint when the `X-API-KEY` header is not set:

```php
->setCheckAccess(function($url, $route, $method, $args) {
    if ($method == "post" && $route == "foo" && !isset($_SERVER['HTTP_X_API_KEY'])) {
        throw new \Exception("Access Denied", 401);
    }
})
```

<!-- panels:end -->

### Other Uses

You can use the [`setCheckAccess()`](phpdoc/#serversetcheckaccess) method for any sort of pre-response logic you wish to execute, such as logging or rate limiting:

<!-- tabs:start -->

#### **Logging**

```php
->setCheckAccess(function($url, $route, $method, $args) {
    $date = new DateTime;
    $log = "User: {$_SERVER['REMOTE_ADDR']} - {$date->format('F j, Y, g:i a')}" . PHP_EOL .
           "URL: {$url}" . PHP_EOL .
           "Method: {$method}" . PHP_EOL .
           "Route: {$route}" . PHP_EOL .
           "Args: " . json_encode($args) . PHP_EOL .
           "---------------------------------------------" . PHP_EOL;
    $logFile = "log_{$date->format('j.n.Y')}.log";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
})
```

#### **Rate Limiting**

```php
->setCheckAccess(function($url, $route, $method, $args) {
    $redis = new \Redis();
    $redis->connect('localhost');

    $key = "api_" . $_SERVER['REMOTE_ADDR'];
    $limit = 10;
    $time = 60;
    
    $count = $redis->get($key);
    if ($count >= $limit)
        throw new \Exception("Rate Limit Exceeded", 429);

    $redis->incr($key);
    $redis->expire($key, $time);
})
```

<!-- tabs:end -->

?> Note: You may only assign one checkAccess function per controller, so if you need to perform multiple pre-response actions for a single controller, you must combine the logic into a single function.

## Responses

By default, the response body is a JSON object containing a status code with the data. If an exception is thrown, the response will contain the error code, the `Exception` class name, and the message.

<!-- tabs:start -->

### **200 OK**
```json
{
    "status": 200,
    "data": true
}
```

### **Missing Argument**
```json
{
    "status": 400,
    "error": "MissingRequiredArgumentException",
    "message": "Argument 'username' is missing"
}
```

### **Custom Exception**

```json
{
    "status": 500,
    "error": "InvalidLoginException",
    "message": "Login is invalid or no access"
}
```

<!-- tabs:end -->

### Exception Handling

The Server will automatically throw an exception and return a 500 error response if:
* The requested route cannot be found
* The server cannot find the specified client
* The server cannot instantiate the specified class
* The requested method cannot be found in the specified class
* A required argument is missing

The following illustrates the expected response when you throw an exception:

<!-- panels:start -->
<!-- div:left-panel -->

#### Exception
```php
throw new \Exception('My custom error');
```

<!-- div:right-panel -->

#### Example Response
```json
{
  "status": 500,
  "error": "Exception",
  "message": "My custom error"
}
```

<!-- panels:end -->

<!-- panels:start -->
<!-- div:left-panel -->

#### Exception
```php
throw new \InvalidArgumentException('Another error', 123);
```

<!-- div:right-panel -->

#### Example Response
```json
{
  "status": 123,
  "error": "InvalidArgumentException",
  "message": "Another error"
}
```

<!-- panels:end -->

#### Custom Exception Handler

You can alternatively provide your own exception handler by using the [`setExceptionHandler()`](phpdoc/#serversetexceptionhandler) method.

```php
// Overwrite error messages based on code
$server->setExceptionHandler(function(\Exception $ex) use ($server) {
    $message = match($ex->getCode()) {
        400 => 'Bad Request',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        default => $ex->getMessage()
    }
    
    $server->getClient()->sendResponse([
        'status' => $ex->getCode(),
        'error' => get_class($ex),
        'message' => $message
    ], $code);
});
```

### Debugging

For verbose error messages, enable debug mode on the desired controller by using the [`setDebugMode(true)`](phpdoc/#serversetdebugmode) method:

<!-- panels:start -->
<!-- div:left-panel -->

#### Example
```php
Server::create('/', MyRestApi\Admin::class)
    ->setDebugMode(true)
    ->collectRoutes()
    ->run();
```

<!-- div:right-panel -->

#### Example Response
```json
{
    "status": 500,
    "error": "InvalidLoginException",
    "message": "Login is invalid or no access",
    "line": 10,
    "file": "libs/MyRestApi/Admin.class.php",
    "trace": ...
}
```

<!-- panels:end -->