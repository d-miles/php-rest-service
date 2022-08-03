# Documentation

## Table of Contents

| Method | Description |
|--------|-------------|
| [**Client**](#Client) | This client handles API responses for a given endpoint. |
| [Client::__construct](#Client__construct) | Create a new client. |
| [Client::setController](#ClientsetController) | Attach client to different controller. |
| [Client::getController](#ClientgetController) | Return the currently attached controller. |
| [Client::sendResponse](#ClientsendResponse) | Sends the actual response. |
| [Client::getOutputFormatMethod](#ClientgetOutputFormatMethod) | Returns the current output format method |
| [Client::getOutputFormat](#ClientgetOutputFormat) | Returns the current output format |
| [Client::getMethod](#ClientgetMethod) | Detect the method. |
| [Client::setMethod](#ClientsetMethod) | Sets a custom http method. |
| [Client::setContentLength](#ClientsetContentLength) | Set header Content-Length from data. |
| [Client::asJSON](#ClientasJSON) | Converts data to pretty json. |
| [Client::asXML](#ClientasXML) | Converts data to xml. |
| [Client::asText](#ClientasText) | Converts data to pretty json. |
| [Client::addOutputFormat](#ClientaddOutputFormat) | Add a additional output format. |
| [Client::setFormat](#ClientsetFormat) | Set the current output format. |
| [Client::getURL](#ClientgetURL) | Returns the current endpoint url. |
| [Client::setURL](#ClientsetURL) | Set the current endpoint url. |
| [Client::setupFormats](#ClientsetupFormats) | Setup formats. |
| [**InternalClient**](#InternalClient) | This client does not send any HTTP data, instead it just returns the value. |
| [InternalClient::sendResponse](#InternalClientsendResponse) | Sends the actual response. |
| [**Server**](#Server) | A REST server class for RESTful APIs. |
| [Server::__construct](#Server__construct) | Constructor |
| [Server::create](#Servercreate) | Factory. |
| [Server::setControllerFactory](#ServersetControllerFactory) |  |
| [Server::getControllerFactory](#ServergetControllerFactory) |  |
| [Server::setHttpStatusCodes](#ServersetHttpStatusCodes) | If the lib should send HTTP status codes. |
| [Server::getHttpStatusCodes](#ServergetHttpStatusCodes) |  |
| [Server::setCheckAccess](#ServersetCheckAccess) | Set the check access function/method. |
| [Server::getCheckAccess](#ServergetCheckAccess) | Getter for checkAccess |
| [Server::setFallbackMethod](#ServersetFallbackMethod) | If this controller can not find a route,we fire this method and send the result. |
| [Server::fallbackMethod](#ServerfallbackMethod) | Getter for fallbackMethod |
| [Server::setDescribeRoutes](#ServersetDescribeRoutes) | Sets whether the service should serve route descriptionsthrough the OPTIONS method. |
| [Server::getDescribeRoutes](#ServergetDescribeRoutes) | Getter for describeRoutes. |
| [Server::setExceptionHandler](#ServersetExceptionHandler) | Send exception function/method. Will be fired if a route-method throws a exception. |
| [Server::getExceptionHandler](#ServergetExceptionHandler) | Getter for checkAccess |
| [Server::setDebugMode](#ServersetDebugMode) | If this is true, we send file, line and backtrace if an exception has been thrown. |
| [Server::getDebugMode](#ServergetDebugMode) | Getter for checkAccess |
| [Server::done](#Serverdone) | Alias for getParentController() |
| [Server::getParentController](#ServergetParentController) | Returns the parent controller |
| [Server::setTriggerUrl](#ServersetTriggerUrl) | Set the URL that triggers the controller. |
| [Server::getTriggerUrl](#ServergetTriggerUrl) | Gets the current trigger url. |
| [Server::setClient](#ServersetClient) | Sets the client. |
| [Server::getClient](#ServergetClient) | Get the current client. |
| [Server::sendBadRequest](#ServersendBadRequest) | Sends a &#039;Bad Request&#039; response to the client. |
| [Server::sendError](#ServersendError) | Sends a &#039;Internal Server Error&#039; response to the client. |
| [Server::sendException](#ServersendException) | Sends a exception response to the client. |
| [Server::addRoute](#ServeraddRoute) | Adds a new route for all http methods (get, post, put, delete, options, head, patch). |
| [Server::addGetRoute](#ServeraddGetRoute) | Same as addRoute, but limits to GET. |
| [Server::addPostRoute](#ServeraddPostRoute) | Same as addRoute, but limits to POST. |
| [Server::addPutRoute](#ServeraddPutRoute) | Same as addRoute, but limits to PUT. |
| [Server::addPatchRoute](#ServeraddPatchRoute) | Same as addRoute, but limits to PATCH. |
| [Server::addHeadRoute](#ServeraddHeadRoute) | Same as addRoute, but limits to HEAD. |
| [Server::addOptionsRoute](#ServeraddOptionsRoute) | Same as addRoute, but limits to OPTIONS. |
| [Server::addDeleteRoute](#ServeraddDeleteRoute) | Same as addRoute, but limits to DELETE. |
| [Server::removeRoute](#ServerremoveRoute) | Removes a route. |
| [Server::setClass](#ServersetClass) | Sets the controller class. |
| [Server::addSubController](#ServeraddSubController) | Attach a sub controller. |
| [Server::normalizeUrl](#ServernormalizeUrl) | Normalize $pUrl. Cuts of the trailing slash. |
| [Server::send](#Serversend) | Sends data to the client with 200 http code. |
| [Server::camelCase2Dashes](#ServercamelCase2Dashes) |  |
| [Server::collectRoutes](#ServercollectRoutes) | Setup automatic routes. |
| [Server::simulateCall](#ServersimulateCall) | Simulates a HTTP Call. |
| [Server::run](#Serverrun) | Fire the magic! |
| [Server::fireMethod](#ServerfireMethod) |  |
| [Server::describe](#Serverdescribe) | Describe a route or the whole controller with all routes. |
| [Server::getMethodMetaData](#ServergetMethodMetaData) | Fetches all meta data informations as params, return type etc. |
| [Server::parsePhpDoc](#ServerparsePhpDoc) | Parse phpDoc string and returns an array. |
| [Server::argumentName](#ServerargumentName) | If the name is a camelcased one whereas the first char is lowercased,then we remove the first char and set first char to lower case. |
| [Server::findRoute](#ServerfindRoute) | Find and return the route for $pUri. |

## Client

This client handles API responses for a given endpoint.

It can format the response as JSON, XML, or plain text.

* Full name: \RestService\Client


### Client::__construct

Create a new client.

```php
Client::__construct( \RestService\Server pServerController ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pServerController` | **\RestService\Server** | The server controller. |


**Return Value:**





---
### Client::setController

Attach client to different controller.

```php
Client::setController( \RestService\Server pServerController ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pServerController` | **\RestService\Server** | Server controller. |


**Return Value:**





---
### Client::getController

Return the currently attached controller.

```php
Client::getController(  ): \RestService\Server
```





**Return Value:**

$pServerController Server controller.



---
### Client::sendResponse

Sends the actual response.

```php
Client::sendResponse( pMessage, string pHttpCode = '200' ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **** | The data to return. |
| `pHttpCode` | **string** | The HTTP code to return. |


**Return Value:**





---
### Client::getOutputFormatMethod

Returns the current output format method

```php
Client::getOutputFormatMethod( string pFormat ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pFormat` | **string** | The output format. &#039;json&#039;, &#039;xml&#039;, &#039;text&#039; |


**Return Value:**

'asJSON', 'asXML', or 'asText'



---
### Client::getOutputFormat

Returns the current output format

```php
Client::getOutputFormat(  ): string
```





**Return Value:**

'json', 'xml', 'text'



---
### Client::getMethod

Detect the method.

```php
Client::getMethod(  ): string
```





**Return Value:**

'get', 'post', 'put', 'delete', 'head', 'options', 'patch'



---
### Client::setMethod

Sets a custom http method.

```php
Client::setMethod( string pMethod ): \RestService\Client
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMethod` | **string** | &#039;get&#039;, &#039;post&#039;, &#039;put&#039;, &#039;delete&#039;, &#039;head&#039;, &#039;options&#039;, &#039;patch&#039; |


**Return Value:**

$this Client instance.



---
### Client::setContentLength

Set header Content-Length from data.

```php
Client::setContentLength( mixed pMessage ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **mixed** | The data to set the header from. |


**Return Value:**





---
### Client::asJSON

Converts data to pretty json.

```php
Client::asJSON( mixed pMessage ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **mixed** | The data to convert. |


**Return Value:**

JSON version of the original data.



---
### Client::asXML

Converts data to xml.

```php
Client::asXML( mixed pMessage, string pParentTagName = '', int pDepth = 1, bool pHeader = true ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **mixed** | The data to convert. |
| `pParentTagName` | **string** | The name of the parent tag. Default is &#039;&#039;. |
| `pDepth` | **int** | The depth of the current tag. Default is 1. |
| `pHeader` | **bool** | Whether to wrap the xml in a header. Default is true. |


**Return Value:**

XML version of the original data.



---
### Client::asText

Converts data to pretty json.

```php
Client::asText( mixed pMessage ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **mixed** | The data to convert. |


**Return Value:**

JSON version of the original data.



---
### Client::addOutputFormat

Add a additional output format.

```php
Client::addOutputFormat( string pCode, string pMethod ): \RestService\Client
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pCode` | **string** | Name of the format. |
| `pMethod` | **string** | Method to use to output the format. |


**Return Value:**

$this Client instance.



---
### Client::setFormat

Set the current output format.

```php
Client::setFormat( string pFormat ): \RestService\Client
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pFormat` | **string** | Name of the format. |


**Return Value:**

$this Client instance.



---
### Client::getURL

Returns the current endpoint url.

```php
Client::getURL(  ): string
```





**Return Value:**

The current endpoint url.



---
### Client::setURL

Set the current endpoint url.

```php
Client::setURL( string pUrl ): \RestService\Client
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUrl` | **string** | The new endpoint url. |


**Return Value:**

$this Client instance.



---
### Client::setupFormats

Setup formats.

```php
Client::setupFormats(  ): \RestService\Client
```





**Return Value:**

$this Client instance.



---
## InternalClient

This client does not send any HTTP data, instead it just returns the value.

Good for testing purposes.

* Full name: \RestService\InternalClient
* Parent class: \RestService\Client


### InternalClient::sendResponse

Sends the actual response.

```php
InternalClient::sendResponse( mixed pMessage, string pHttpCode = '200' ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMessage` | **mixed** | The data to process. |
| `pHttpCode` | **string** | The HTTP code to process. |


**Return Value:**

HTTP method of current request.



---
## Server

A REST server class for RESTful APIs.



* Full name: \RestService\Server


### Server::__construct

Constructor

```php
Server::__construct( string pTriggerUrl, string|object pControllerClass = null, \RestService\Server pParentController = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pTriggerUrl` | **string** |  |
| `pControllerClass` | **string\|object** |  |
| `pParentController` | **\RestService\Server** |  |


**Return Value:**





---
### Server::create

Factory.

```php
Server::create( string pTriggerUrl, string pControllerClass = '' ): \RestService\Server
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pTriggerUrl` | **string** |  |
| `pControllerClass` | **string** |  |


**Return Value:**

$this



---
### Server::setControllerFactory



```php
Server::setControllerFactory( callable controllerFactory ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `controllerFactory` | **callable** |  |


**Return Value:**

$this



---
### Server::getControllerFactory



```php
Server::getControllerFactory(  ): callable
```





**Return Value:**





---
### Server::setHttpStatusCodes

If the lib should send HTTP status codes.

```php
Server::setHttpStatusCodes( bool pWithStatusCode ): \RestService\Server
```

Some Client libs does not support it.


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pWithStatusCode` | **bool** |  |


**Return Value:**

$this



---
### Server::getHttpStatusCodes



```php
Server::getHttpStatusCodes(  ): bool
```





**Return Value:**





---
### Server::setCheckAccess

Set the check access function/method.

```php
Server::setCheckAccess( callable pFn ): \RestService\Server
```

Will fired with arguments: (url, route)


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pFn` | **callable** |  |


**Return Value:**

$this



---
### Server::getCheckAccess

Getter for checkAccess

```php
Server::getCheckAccess(  ): callable
```





**Return Value:**





---
### Server::setFallbackMethod

If this controller can not find a route,
we fire this method and send the result.

```php
Server::setFallbackMethod( string pFn ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pFn` | **string** | Methodname of current attached class |


**Return Value:**

$this



---
### Server::fallbackMethod

Getter for fallbackMethod

```php
Server::fallbackMethod(  ): string
```





**Return Value:**





---
### Server::setDescribeRoutes

Sets whether the service should serve route descriptions
through the OPTIONS method.

```php
Server::setDescribeRoutes( bool pDescribeRoutes ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pDescribeRoutes` | **bool** |  |


**Return Value:**

$this



---
### Server::getDescribeRoutes

Getter for describeRoutes.

```php
Server::getDescribeRoutes(  ): bool
```





**Return Value:**





---
### Server::setExceptionHandler

Send exception function/method. Will be fired if a route-method throws a exception.

```php
Server::setExceptionHandler( callable pFn ): \RestService\Server
```

Please die/exit in your function then.
Arguments: (exception)


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pFn` | **callable** |  |


**Return Value:**

$this



---
### Server::getExceptionHandler

Getter for checkAccess

```php
Server::getExceptionHandler(  ): callable
```





**Return Value:**





---
### Server::setDebugMode

If this is true, we send file, line and backtrace if an exception has been thrown.

```php
Server::setDebugMode( bool pDebugMode ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pDebugMode` | **bool** |  |


**Return Value:**

$this



---
### Server::getDebugMode

Getter for checkAccess

```php
Server::getDebugMode(  ): bool
```





**Return Value:**





---
### Server::done

Alias for getParentController()

```php
Server::done(  ): \RestService\Server
```





**Return Value:**





---
### Server::getParentController

Returns the parent controller

```php
Server::getParentController(  ): \RestService\Server
```





**Return Value:**

$this



---
### Server::setTriggerUrl

Set the URL that triggers the controller.

```php
Server::setTriggerUrl( pTriggerUrl ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pTriggerUrl` | **** |  |


**Return Value:**





---
### Server::getTriggerUrl

Gets the current trigger url.

```php
Server::getTriggerUrl(  ): string
```





**Return Value:**





---
### Server::setClient

Sets the client.

```php
Server::setClient( \RestService\Client|string pClient ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pClient` | **\RestService\Client\|string** |  |


**Return Value:**

$this



---
### Server::getClient

Get the current client.

```php
Server::getClient(  ): \RestService\Client
```





**Return Value:**





---
### Server::sendBadRequest

Sends a 'Bad Request' response to the client.

```php
Server::sendBadRequest( pCode, pMessage ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pCode` | **** |  |
| `pMessage` | **** |  |


**Return Value:**





---
### Server::sendError

Sends a 'Internal Server Error' response to the client.

```php
Server::sendError( pCode, pMessage ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pCode` | **** |  |
| `pMessage` | **** |  |


**Return Value:**





---
### Server::sendException

Sends a exception response to the client.

```php
Server::sendException( pException ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pException` | **** |  |


**Return Value:**





---
### Server::addRoute

Adds a new route for all http methods (get, post, put, delete, options, head, patch).

```php
Server::addRoute( string pUri, callable|string pCb, string pHttpMethod = '_all_' ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |
| `pHttpMethod` | **string** | If you want to limit to a HTTP method. |


**Return Value:**





---
### Server::addGetRoute

Same as addRoute, but limits to GET.

```php
Server::addGetRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addPostRoute

Same as addRoute, but limits to POST.

```php
Server::addPostRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addPutRoute

Same as addRoute, but limits to PUT.

```php
Server::addPutRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addPatchRoute

Same as addRoute, but limits to PATCH.

```php
Server::addPatchRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addHeadRoute

Same as addRoute, but limits to HEAD.

```php
Server::addHeadRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addOptionsRoute

Same as addRoute, but limits to OPTIONS.

```php
Server::addOptionsRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::addDeleteRoute

Same as addRoute, but limits to DELETE.

```php
Server::addDeleteRoute( string pUri, callable|string pCb ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pCb` | **callable\|string** | The method name of the passed controller or a php callable. |


**Return Value:**





---
### Server::removeRoute

Removes a route.

```php
Server::removeRoute( string pUri ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |


**Return Value:**





---
### Server::setClass

Sets the controller class.

```php
Server::setClass( string|object pClass ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pClass` | **string\|object** |  |


**Return Value:**





---
### Server::addSubController

Attach a sub controller.

```php
Server::addSubController( string pTriggerUrl, mixed pControllerClass = '' ): \RestService\Server
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pTriggerUrl` | **string** |  |
| `pControllerClass` | **mixed** | A class name (autoloader required) or a instance of a class. |


**Return Value:**

new created Server. Use done() to switch the context back to the parent.



---
### Server::normalizeUrl

Normalize $pUrl. Cuts of the trailing slash.

```php
Server::normalizeUrl( string &pUrl ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUrl` | **string** |  |


**Return Value:**





---
### Server::send

Sends data to the client with 200 http code.

```php
Server::send( pData ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pData` | **** |  |


**Return Value:**





---
### Server::camelCase2Dashes



```php
Server::camelCase2Dashes( string pValue ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pValue` | **string** |  |


**Return Value:**





---
### Server::collectRoutes

Setup automatic routes.

```php
Server::collectRoutes(  ): \RestService\Server
```





**Return Value:**





---
### Server::simulateCall

Simulates a HTTP Call.

```php
Server::simulateCall( string pUri, string pMethod = 'get' ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pMethod` | **string** | The HTTP Method |


**Return Value:**





---
### Server::run

Fire the magic!

```php
Server::run(  ): mixed
```

Searches the method and sends the data to the client.



**Return Value:**





---
### Server::fireMethod



```php
Server::fireMethod( mixed pMethod, mixed pController, mixed pArguments ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMethod` | **mixed** |  |
| `pController` | **mixed** |  |
| `pArguments` | **mixed** |  |


**Return Value:**





---
### Server::describe

Describe a route or the whole controller with all routes.

```php
Server::describe( string pUri = null, bool pOnlyRoutes = false ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pOnlyRoutes` | **bool** |  |


**Return Value:**





---
### Server::getMethodMetaData

Fetches all meta data informations as params, return type etc.

```php
Server::getMethodMetaData( \ReflectionMethod pMethod, array pRegMatches = null ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pMethod` | **\ReflectionMethod** |  |
| `pRegMatches` | **array** |  |


**Return Value:**





---
### Server::parsePhpDoc

Parse phpDoc string and returns an array.

```php
Server::parsePhpDoc( string pString ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pString` | **string** |  |


**Return Value:**





---
### Server::argumentName

If the name is a camelcased one whereas the first char is lowercased,
then we remove the first char and set first char to lower case.

```php
Server::argumentName( string pName ): string
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pName` | **string** |  |


**Return Value:**





---
### Server::findRoute

Find and return the route for $pUri.

```php
Server::findRoute( string pUri, string pMethod = '_all_' ): array|bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `pUri` | **string** |  |
| `pMethod` | **string** | limit to method. |


**Return Value:**





---
