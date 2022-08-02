# PHP-REST-Service

PHP-REST-Service is a simple and fast PHP class for RESTful JSON APIs.

![Build Status](https://img.shields.io/circleci/build/github/cdgco/php-rest-service?style=flat-square)

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

### Composer

Run `php composer require cdgco/php-rest-service`, then include the Composer autoloader with `include 'vendor/autoload.php';`.

### Manual

Copy `Server.php` to your directory and include with `include 'Server.php';`.

## Web Server Configuration

PHP-REST-Service acts as a single page application, so all requests must be sent to the index.php file (or the file you want to serve).

For example, on apache, you can add the following lines to your `.htaccess` file:

```
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.+) index.php/$1 [L,QSA]
```

Usage Demo
----------

### Way 1. The dirty & fast


```php

use RestService\Server;

Server::create('/')
    ->addGetRoute('test', function(){
        return 'Yay!';
    })
    ->addGetRoute('foo/(.*)', function($bar){
        return $bar;
    })
    ->addPostRoute('foo', function($field1, $field2) {
      // do stuff with $field1, $field2 etc
      // or you can directly get them with $_POST['field1']
    })
->run();

```

### Way 2. Auto-Collection

`index.php`:

```php

use RestService\Server;

Server::create('/admin', 'myRestApi\Admin')
    ->collectRoutes()
->run();

```

`MyRestApi/Admin.php`:

```php

namespace MyRestApi;

class Admin {

    /**
    * Checks if a user is logged in.
    *
    * @return boolean
    */
    public function getLoggedIn(){
        return $this->getContainer('auth')->isLoggedIn();
    }

    /**
    * @param string $username
    * @param string $password
    * return boolean
    */
    public function postLogin($username, $password){
        return $this->getContainer('auth')->doLogin($username, $password);
    }

    /**
     * @param string $server
     * @url stats/([0-9]+)
     * @url stats
     * @return string
     */
    public function getStats($server = '1'){
        return $this->getServerStats($server);
    }

}

```

Generates following entry points:
```
    + GET  /admin/logged-in
    + POST /admin/login?username=&password=
    + GET  /admin/stats/([0-9]+)
    + GET  /admin/stats
```


### Way 3. Custom rules with controller

`index.php`:

```php

use RestService\Server;

Server::create('/admin', new MyRestApi\Admin) //base entry points `/admin`
    ->setDebugMode(true) //prints the debug trace, line number and file if a exception has been thrown.

    ->addGetRoute('login', 'doLogin') // => /admin/login
    ->addGetRoute('logout', 'doLogout') // => /admin/logout

    ->addGetRoute('page', 'getPages')
    ->addPutRoute('page', 'addPage')
    ->addGetRoute('page/([0-9]+)', 'getPage')
    ->addDeleteRoute('page/([0-9]+)', 'deletePage')
    ->addPostRoute('page/([0-9]+)', 'updatePage')

    ->addGetRoute('foo/bar/too', 'doFooBar')

    ->addSubController('tools', \RestApi\Tools) //adds a new sub entry point 'tools' => admin/tools
        ->addDeleteRoute('cache', 'clearCache')
        ->addGetRoute('rebuild-index', 'rebuildIndex')
    ->done()

->run();

```

`MyRestApi/Admin.php`:

```php

namespace MyRestApi;

class Admin {
    public function login($username, $password){

        if (!$this->validLogin($username, $password))
            throw new InvalidLoginException('Login is invalid or no access.');

        return $this->getToken();

    }

    public function logout(){

        if (!$this->hasSession()){
            throw new NoCurrentSessionException('There is no current session.');
        }

        return $this->killSession();

    }

    public function getPage($id){
        //...
    }
}

namespace RestAPI;

class Tools {
    /**
    * Clears the cache of the app.
    *
    * @param boolean $withIndex If true, it clears the search index too.
    * @return boolean True if the cache has been cleared.
    */
    public function clearCache($withIndex = false){
        return true;
    }
}
```


## Responses

The response body is always a array (JSON per default) containing a status code and the actual data.
If a exception has been thrown, it contains the status 500, the exception class name as error and the message as message.

Some examples:

```
+ GET admin/login?username=foo&password=bar
  =>
  {
     "status": "200",
     "data": true
  }

+ GET admin/login?username=foo&password=invalidPassword
  =>
  {
     "status": "500",
     "error": "InvalidLoginException",
     "message": "Login is invalid or no access"
  }

+ GET admin/login
  =>
  {
     "status: "400",
     "error": "MissingRequiredArgumentException",
     "message": "Argument 'username' is missing"
  }

+ GET admin/login?username=foo&password=invalidPassword
  With active debugMode we'll get:
  =>
  {
     "status": "500",
     "error": "InvalidLoginException",
     "message": "Login is invalid or no access",
     "line": 10,
     "file": "libs/RestAPI/Admin.class.php",
     "trace": <debugTrace>
  }

+ GET admin/tools/cache
  =>
  {
     "status": 200,
     "data": true
  }
```

License
-------

Licensed under the MIT License. See the LICENSE file for more details.
