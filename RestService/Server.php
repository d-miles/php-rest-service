<?php

namespace RestService;

/**
 * A REST server class for RESTful APIs.
 */
class Server
{
    /**
     * Current routes.
     * 
     * structure:
     *  [
     *    '<uri>' => <callable>
     *  ]
     * 
     * @var array
     */
    protected $routes = [];

    /**
     * Blacklisted query string arguments.
     * 
     * @var array
     */
    protected $blacklistedGetParameters = ['_method', '_suppress_status_code'];

    /**
     * Current URL that triggers the controller.
     * 
     * @var string
     */
    protected $triggerUrl = '';

    /**
     * Contains the controller object.
     * 
     * @var string|object
     */
    protected $controller = '';

    /**
     * List of sub controllers.
     * 
     * @var array
     */
    protected $controllers = [];

    /**
     * Parent controller.
     * 
     * @var Server
     */
    protected $parentController;

    /**
     * The client
     * 
     * @var Client
     */
    protected $client;

    /**
     * OpenAPI Specification.
     * 
     * @var array
     */
    protected $apiSpec;

    /**
     * List of excluded method names.
     * 
     * @var array|string ['methodOne', 'methodTwo'] or * for all methods
     */
    protected $collectRoutesExclude = ['__construct'];

    /**
     * List of possible request methods.
     * 
     * @var array
     */
    public $methods = ['get', 'post', 'put', 'delete', 'head', 'options', 'patch'];

    /**
     * Check access function/method. Will be fired after the route has been found.
     * 
     * Arguments: (string $url, string $routeUri, string $method, array $args)
     * 
     * @var callable
     */
    protected $checkAccessFn;

    /**
     * Send exception function/method. Will be fired if a route-method throws a exception.
     * 
     * Arguments: (\Exception $ex)
     * 
     * @var callable
     */
    protected $sendExceptionFn;

    /**
     * If this is true, we send file, line and backtrace if an exception has been thrown.
     * 
     * @var boolean
     */
    protected $debugMode = false;

    /**
     * Sets whether the service should serve route descriptions
     * through the OPTIONS method.
     * 
     * @var boolean
     */
    protected $describeRoutes = true;

    /**
     * If this controller can not find a route,
     * we fire this method and send the result.
     * 
     * @var string
     */
    protected $fallbackMethod = '';

    /**
     * If the lib should send HTTP status codes.
     * Some Client libs does not support this, you can deactivate it via
     * ->setHttpStatusCodes(false);
     * 
     * @var boolean
     */
    protected $withStatusCode = true;

    /**
     * @var callable
     */
    protected $controllerFactory;

    /**
     * @var callable
     */
    private function declaresArray(\ReflectionParameter $reflectionParameter): bool {
        $reflectionType = $reflectionParameter->getType();
    
        if (!$reflectionType)
            return false;
    
        $types = $reflectionType instanceof \ReflectionUnionType
            ? $reflectionType->getTypes()
            : [$reflectionType];
    
        return in_array('array', array_map(fn(\ReflectionNamedType $t) => $t->getName(), $types));
    }

    /**
     * Create a new server.
     * 
     * @param string              $pTriggerUrl The URL that triggers the controller.
     * @param string|object       $pControllerClass The default endpoint function class.
     * @param Server $pParentController The parent controller.
     * @return void
     */
    public function __construct(string $pTriggerUrl, string|object|null $pControllerClass = null, ?Server $pParentController = null) {
        $this->normalizeUrl($pTriggerUrl);

        if ($pParentController) {
            $this->parentController = $pParentController;
            $this->setClient($pParentController->getClient());
            
            if ($pParentController->getCheckAccess())
                $this->setCheckAccess($pParentController->getCheckAccess());
            
            if ($pParentController->getExceptionHandler())
                $this->setExceptionHandler($pParentController->getExceptionHandler());

            if ($pParentController->getDebugMode())
                $this->setDebugMode($pParentController->getDebugMode());

            if ($pParentController->getDescribeRoutes())
                $this->setDescribeRoutes($pParentController->getDescribeRoutes());

            if ($pParentController->getControllerFactory())
                $this->setControllerFactory($pParentController->getControllerFactory());

            $this->setHttpStatusCodes($pParentController->getHttpStatusCodes());

        } else {
            $this->setClient(new Client($this));
        }

        $this->setClass($pControllerClass);
        $this->setTriggerUrl($pTriggerUrl);
    }

    /**
     * Creates controller factory. Used for internal testing.
     * 
     * @param string $pTriggerUrl The URL that triggers the controller.
     * @param string $pControllerClass The default endpoint function class.
     * 
     * @return Server The server instance.
     */
    public static function create($pTriggerUrl, $pControllerClass = '') {
        $class = get_called_class();
        
        return new $class($pTriggerUrl, $pControllerClass);
    }

    /**
     * Change the current controller factory.
     * 
     * @param callable $controllerFactory
     * 
     * @return Server The server instance.
     */
    public function setControllerFactory(callable $controllerFactory) {
        $this->controllerFactory = $controllerFactory;

        return $this;
    }

    /**
     * Return the current controller factory.
     * 
     * @return callable The controller factory.
     */
    public function getControllerFactory() {
        return $this->controllerFactory;
    }
    
    /**
     * Return the current controller.
     * 
     * @return string|object The current controller.
     */
    public function getController() : string|object {
        return $this->controller;
    }
    
    /**
     * Return a list of all the registered subcontrollers.
     * 
     * @return array Subcontrollers.
     */
    public function getSubControllers() : array {
        return $this->controllers;
    }
    
    /**
     * Return a list of all routes for this controller.
     * 
     * @return array Controller routes.
     */
    public function getRoutes() : array {
        return $this->routes;
    }

    /**
     * Enable and set parameters for OpenAPI specification generateion.
     * 
     * @param string $title The name of the controller.
     * @param string $version The version of the controller.
     * @param string $desciption The description of the server. Default is null.
     * @param string $server The address of the server. Default is null.
     * @param array $additionalInfo Additional info for the OpenAPI specification. Default is an empty array.
     * @param bool $recurse Whether or not to recurse into the child controllers. Default is true.
     * @return Server The server instance.
     */
    public function setApiSpec(string $title, string $version, ?string $description = null, ?string $server = null, array $additionalInfo = [], bool $recurse = true) {
        $this->apiSpec = [
            'title' => $title,
            'version' => $version,
            'description' => $description,
            'server' => $server, 
            'info' => $additionalInfo,
            'recurse' => $recurse
        ];

        return $this;
    }

    /**
     * Return the current controller factory.
     * 
     * @return array The API specification.
     */
    public function getApiSpec() {
        return $this->apiSpec;
    }

    /**
     * Enable / Disable sending of HTTP status codes.
     * 
     * @param  boolean $pWithStatusCode If true, send HTTP status codes.
     * @return Server                   The server instance.
     */
    public function setHttpStatusCodes($pWithStatusCode) {
        $this->withStatusCode = $pWithStatusCode;

        return $this;
    }

    /**
     * Return if HTTP status codes are sent.
     * 
     * @return boolean If true, send HTTP status codes.
     */
    public function getHttpStatusCodes() {
        return $this->withStatusCode;
    }

    /**
     * Set the check access function/method.
     * 
     * @param  callable $pFn    The check access function/method. Arguments: (string $url, string $routeUri, string $method, array $args)
     * @return Server           The server instance.
     */
    public function setCheckAccess($pFn) {
        $this->checkAccessFn = $pFn;

        return $this;
    }

    /**
     * Returns the current check access function/method.
     * 
     * @return callable The check access function/method.
     */
    public function getCheckAccess() {
        return $this->checkAccessFn;
    }

    /**
     * Set fallback method if no route is found.
     * 
     * @param  string $pMethodName  The fallback method.
     * @return Server               The server instance.
     */
    public function setFallbackMethod($pMethodName) {
        $this->fallbackMethod = $pMethodName;

        return $this;
    }

    /**
     * Returns the fallback method.
     * 
     * @return string The fallback method.
     */
    public function getFallbackMethod() {
        return $this->fallbackMethod;
    }

    /**
     * Sets whether the service should serve route descriptions
     * through the OPTIONS method.
     * 
     * @param  boolean $pDescribeRoutes If true, serve route descriptions.
     * @return Server                   The server instance.
     */
    public function setDescribeRoutes($pDescribeRoutes) {
        $this->describeRoutes = $pDescribeRoutes;

        return $this;
    }

    /**
     * Returns whether the service should serve route descriptions
     * 
     * @return boolean If true, serve route descriptions.
     */
    public function getDescribeRoutes() {
        return $this->describeRoutes;
    }

    /**
     * Send exception function/method. Will be fired if a route-method throws a exception.
     * 
     * @param  callable $pFn    The exception function/method. Arguments: (\Exception $ex)
     * @return Server           The server instance.
     */
    public function setExceptionHandler($pFn) {
        $this->sendExceptionFn = $pFn;

        return $this;
    }

    /**
     * Returns the current exception handler function/method.
     * 
     * @return callable The exception handler function/method.
     */
    public function getExceptionHandler() {
        return $this->sendExceptionFn;
    }

    /**
     * If this is true, we send file, line and backtrace if an exception has been thrown.
     * 
     * @param  boolean $pDebugMode  If true, send debug info.
     * @return Server               The server instance.
     */
    public function setDebugMode($pDebugMode) {
        $this->debugMode = $pDebugMode;

        return $this;
    }

    /**
     * Returns if debug mode is enabled.
     * 
     * @return boolean If true, send debug info.
     */
    public function getDebugMode() {
        return $this->debugMode;
    }

    /**
     * Alias for getParentController()
     * 
     * @return Server The server instance.
     */
    public function done() {
        return $this->getParentController();
    }

    /**
     * Returns the parent controller
     * 
     * @return Server The server instance.
     */
    public function getParentController() {
        return $this->parentController;
    }

    /**
     * Set the URL that triggers the controller.
     * 
     * @param $pTriggerUrl  The URL that triggers the controller.
     * @return Server       The server instance.
     */
    public function setTriggerUrl($pTriggerUrl) {
        $this->triggerUrl = $pTriggerUrl;

        return $this;
    }

    /**
     * Gets the current trigger url.
     * 
     * @return string The trigger url.
     */
    public function getTriggerUrl() {
        return $this->triggerUrl;
    }

    /**
     * Sets the client.
     * 
     * @param  Client|string $pClient   The endpoint client.
     * @return Server                   The server instance.
     */
    public function setClient($pClient) {
        if (is_string($pClient)) {
            $pClient = new $pClient($this);
        }

        $this->client = $pClient;
        $this->client->setupFormats();

        return $this;
    }

    /**
     * Get the current client.
     * 
     * @return Client The client.
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * Sends a 'Bad Request' response to the client.
     * 
     * @param $pCode The HTTP status code.
     * @param $pMessage The message to send.
     * @throws \Exception If the client is not set.
     * @return string The response.
     */
    public function sendBadRequest($pCode, $pMessage) {
        if (is_object($pMessage) && property_exists($pMessage, 'xdebug_message')) 
            $pMessage = $pMessage->xdebug_message;
        $msg = [
            'error' => $pCode, 
            'message' => $pMessage
        ];
        if (!$this->getClient())
            throw new \Exception('client_not_found_in_ServerController');
        return $this->getClient()->sendResponse($msg, '400');
    }

    /**
     * Sends a 'Internal Server Error' response to the client.
     * 
     * @param $pCode The HTTP status code.
     * @param $pMessage The message to send.
     * @throws \Exception If the client is not set.
     * @return string The response.
     */
    public function sendError($pCode, $pMessage) {
        if (is_object($pMessage) && property_exists($pMessage, 'xdebug_message'))
            $pMessage = $pMessage->xdebug_message;
        $msg = [
            'error' => $pCode,
            'message' => $pMessage
        ];
        if (!$this->getClient())
            throw new \Exception('client_not_found_in_ServerController');
        return $this->getClient()->sendResponse($msg, '500');
    }

    /**
     * Sends a exception response to the client.
     * 
     * @param $pException The exception to send.
     * @throws \Exception If the client is not set.
     * @return void
     */
    public function sendException($pException) {
        if ($this->sendExceptionFn) {
            if ($this->sendExceptionFn instanceof \Closure) {
                $this->sendExceptionFn->call($this->controller, $pException);
            } else {
                call_user_func_array($this->sendExceptionFn, [$pException]);
            }
        }
        
        $message = $pException->getMessage();
        if (is_object($message) && property_exists($message, 'xdebug_message'))
            $message = $message->xdebug_message;

        $msg = [
            'error' => get_class($pException),
            'message' => $message
        ];

        $code = '500';
        if ($pException->getCode() != 0)
            $code = $pException->getCode();

        if ($this->debugMode) {
            $msg['file'] = $pException->getFile();
            $msg['line'] = $pException->getLine();
            $msg['trace'] = $pException->getTraceAsString();
        }

        if (!$this->getClient())
            throw new \Exception('client_not_found_in_ServerController');
        return $this->getClient()->sendResponse($msg, $code);
    }

    /**
     * Adds a new route for all http methods (get, post, put, delete, options, head, patch).
     * 
     * @param  string          $pUri        The uri to match.
     * @param  callable|string $pCb         The method name of the passed controller or a php callable.
     * @param  string          $pHttpMethod If you want to limit to a HTTP method.
     * @return Server                       The server instance.
     */
    public function addRoute($pUri, $pCb, $pHttpMethod = '_all_') {
        $this->routes[$pUri][ $pHttpMethod ] = $pCb;

        return $this;
    }

    /**
     * Same as addRoute, but limits to GET.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addGetRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'get');

        return $this;
    }

    /**
     * Same as addRoute, but limits to POST.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addPostRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'post');

        return $this;
    }

    /**
     * Same as addRoute, but limits to PUT.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addPutRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'put');

        return $this;
    }

    /**
     * Same as addRoute, but limits to PATCH.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addPatchRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'patch');

        return $this;
    }

    /**
     * Same as addRoute, but limits to HEAD.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addHeadRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'head');

        return $this;
    }

    /**
     * Same as addRoute, but limits to OPTIONS.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addOptionsRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'options');

        return $this;
    }

    /**
     * Same as addRoute, but limits to DELETE.
     * 
     * @param  string          $pUri    The uri to match.
     * @param  callable|string $pCb     The method name of the passed controller or a php callable.
     * @return Server                   The server instance.
     */
    public function addDeleteRoute($pUri, $pCb) {
        $this->addRoute($pUri, $pCb, 'delete');

        return $this;
    }

    /**
     * Removes a route.
     * 
     * @param  string   $pUri   The uri to match.
     * @return Server           The server instance.
     */
    public function removeRoute($pUri) {
        unset($this->routes[$pUri]);

        return $this;
    }

    /**
     * Sets the controller class to use for function endpoints.
     * 
     * @param   string|object   $pClass The class name or object.
     * @return  void
     */
    public function setClass($pClass) {
        if (is_string($pClass)) {
            $this->createControllerClass($pClass);
        } elseif (is_object($pClass)) {
            $this->controller = $pClass;
        } else {
            $this->controller = $this;
        }
    }

    /**
     * Setup the controller class.
     * 
     * @param  string       $pClassName
     * @throws \Exception   If the class does not exist.
     */
    protected function createControllerClass($pClassName) {
        if ($pClassName != '') {
            try {
                if ($this->controllerFactory) {
                    $this->controller = call_user_func_array($this->controllerFactory, [$pClassName, $this]);
                } else {
                    $this->controller = new $pClassName($this);
                }
                if ($this->controller instanceof static) {
                    $this->controller->setClient($this->getClient());
                }
            } catch (\Exception $e) {
                throw new \Exception('Error during initialisation of '.$pClassName.': '.$e, 0, $e);
            }
        } else {
            $this->controller = $this;
        }
    }

    /**
     * Attach a sub controller to this controller.
     * 
     * @param string    $pTriggerUrl        The url to trigger the sub controller.
     * @param mixed     $pControllerClass   A class name (autoloader required) or an instance of a class.
     * @return Server                       A newly-created Server controller. Use done() to switch the context back to the parent.
     */
    public function addSubController($pTriggerUrl, $pControllerClass = '') {
        $this->normalizeUrl($pTriggerUrl);

        $base = $this->triggerUrl;
        if ($base == '/')
            $base = '';

        $controller = new Server($base . $pTriggerUrl, $pControllerClass, $this);

        $this->controllers[] = $controller;

        return $controller;
    }

    /**
     * Normalize $pUrl by trimming the trailing slash.
     * 
     * @param  string $pUrl The url to normalize.
     * @return void
     */
    public function normalizeUrl(&$pUrl) {
        if ('/' === $pUrl)
            return;
        if ($pUrl != null) {
            if (substr($pUrl, -1) == '/')
                $pUrl = substr($pUrl, 0, -1);
            if (substr($pUrl, 0, 1) != '/')
                $pUrl = '/' . $pUrl;
        }
    }

    /**
     * Sends data to the client with 200 http code.
     * 
     * @param $pData The data to send.
     * @return void
     */
    public function send($pData, $unescape = 0) {
        return $this->getClient()->sendResponse(['data' => $pData], 200, $unescape);
    }

    /**
     * Convert string from camel case to dashes.
     * 
     * @param  string $pValue The string to convert.
     * @return string The converted string.
     */
    public function camelCase2Dashes($pValue) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $pValue));
    }

    /**
     * Automatically collect routes from class.
     * 
     * @return Server The server instance.
     */
    public function collectRoutes() {
        if ($this->collectRoutesExclude == '*')
            return $this;

        $methods = get_class_methods($this->controller);
        foreach ($methods as $method) {
            if (in_array($method, $this->collectRoutesExclude))
                continue;

            $info = explode('/', preg_replace('/([a-z]*)(([A-Z]+)([a-zA-Z0-9_]*))/', '$1/$2', $method));
            $uri  = $this->camelCase2Dashes((empty($info[1]) ? '' : $info[1]));

            $httpMethod  = $info[0];
            if ($httpMethod == 'all') {
                $httpMethod = '_all_';
            }

            $reflectionMethod = new \ReflectionMethod($this->controller, $method);
            if ($reflectionMethod->isPrivate())
                continue;

            $phpDocs = $this->getMethodMetaData($reflectionMethod);
            if (array_key_exists('url', $phpDocs)) {
                if (is_string($phpDocs['url'])) {
                    //only one route
                    $this->routes[$phpDocs['url']][$httpMethod] = $method;
                } else {
                    foreach($phpDocs['url'] as $urlAnnotation) {
                        $this->routes[$urlAnnotation][$httpMethod] = $method;
                    }
                }
            } else {
                $this->routes[$uri][$httpMethod] = $method;
            }
        }

        return $this;
    }

    /**
     * Simulates a HTTP Call.
     * 
     * @param  string $pUri     The uri to call.
     * @param  string $pMethod  The HTTP Method
     * @return string           The response.
     */
    public function simulateCall($pUri, $pMethod = 'get') {
        if (($idx = strpos($pUri, '?')) !== false) {
            parse_str(substr($pUri, $idx+1), $_GET);
            $pUri = substr($pUri, 0, $idx);
        }
        $this->getClient()->setURL($pUri);
        $this->getClient()->setMethod($pMethod);

        return $this->run();
    }

    /**
     * Fire the magic!
     * 
     * Searches the method and sends the data to the client.
     * 
     * @return mixed The response.
     */
    public function run() {
        if ($this->getApiSpec() !== null) {
            $this->addSubController('', new OpenApiController($this, $this->getApiSpec()))
                ->setClient(OpenApiClient::class)
                ->collectRoutes();
        }
        
        //check sub controller
        foreach ($this->controllers as $controller) {
            if ($result = $controller->run()) {
                return $result;
            }
        }
        
        $requestedUrl = $this->getClient()->getURL();
        $this->normalizeUrl($requestedUrl);
        
        //check if its in our area
        if ($requestedUrl !== null && strpos($requestedUrl, $this->triggerUrl) !== 0)
            return;

        $endPos = $this->triggerUrl === '/'
                    ? 1
                    : strlen($this->triggerUrl) + 1;

        if ($requestedUrl != null) {
            $uri = substr($requestedUrl, $endPos);
        }
        else {
            $uri = '';
        }

        if (!$uri) $uri = '';

        $arguments = [];
        $requiredMethod = $this->getClient()->getMethod();

        //does the requested uri exist?
        list($callableMethod, $regexArguments, $method, $routeUri) = $this->findRoute($uri, $requiredMethod);

        // If request for options, send route description
        if ((!$callableMethod || $method != 'options') && $requiredMethod == 'options') {
            $description = $this->describe($uri);
            $this->send($description);
        }
        
        // If route is not found, return fallback method or error
        if (!$callableMethod) {
            if (!$this->getParentController()) {
                if ($this->fallbackMethod) {
                    $m = $this->fallbackMethod;
                    $this->send($this->controller->$m());
                } else {
                    return $this->sendBadRequest('RouteNotFoundException', "There is no route for '$uri'.");
                }
            } else {
                return false;
            }
        }

        if ($method == '_all_')
            $arguments[] = $method;

        if (is_array($regexArguments)) {
            $arguments = array_merge($arguments, $regexArguments);
        }

        //open class and scan method
        if ($this->controller && is_string($callableMethod)) {
            if (!method_exists($this->controller, $callableMethod)) {
                $this->sendBadRequest('MethodNotFoundException', "There is no method '$callableMethod' in ".
                    get_class($this->controller).".");
            }
            
            $ref = new \ReflectionClass($this->controller);
            $reflectionMethod = $ref->getMethod($callableMethod);
        } else if (is_callable($callableMethod)) {
            $reflectionMethod = new \ReflectionFunction($callableMethod);
        }
        
        $params = $reflectionMethod->getParameters();
        
        if ($method == '_all_') {
            //first parameter is $pMethod
            array_shift($params);
        }
        
        //remove regex arguments
        for ($i=0; $i<count($regexArguments); $i++) {
            array_shift($params);
        }
        
        //collect arguments
        foreach ($params as $param) {
            $name = $this->argumentName($param->getName());

            // If argument is _ (underscore), pass all arguments
            if ($name == '_') {
                $thisArgs = [];
                foreach ($_GET as $k => $v) {
                    if (substr($k, 0, 1) == '_' && $k != '_suppress_status_code')
                        $thisArgs[$k] = $v;
                }
                $arguments[] = $thisArgs;
            } 
            // Else, pass the named argument
            else {

                // Get PUT data (also supports JSON encoded POST data)
                $_PUT = null;

                if (isset($_SERVER['REQUEST_METHOD'])) {
                    $method = $_SERVER['REQUEST_METHOD'];
                    if ('PUT' === $method || 'POST' === $method) {
                        $input = file_get_contents("php://input");
                        if ($input) {
                            try {
                                $_PUT = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                            } catch (\JsonException) {
                                $_PUT = parse_str($input, $_PUT);
                            }
                        }
                    }
                }

                if (!$param->isOptional() && !isset($_GET[$name]) && !isset($_POST[$name]) && !isset($_PUT[$name])) {
                    return $this->sendBadRequest('MissingRequiredArgumentException', sprintf("Argument '%s' is missing.", $name));
                }
                
                $arguments[] = $_GET[$name] ?? $_POST[$name] ?? $_PUT[$name] ?? $param->getDefaultValue();
            }
        }

        if ($this->checkAccessFn) {
            $args[] = $this->getClient()->getURL();
            $args[] = $routeUri;
            $args[] = $method;
            $args[] = $arguments;
            try {
                if ($this->checkAccessFn instanceof \Closure) {
                    $this->checkAccessFn->call($this->controller, ...$args);
                } else {
                    call_user_func_array($this->checkAccessFn, $args);
                }
            } catch (\Exception $e) {
                $this->sendException($e);
            }
        }

        //fire method
        $object = $this->controller;

        return $this->fireMethod($callableMethod, $object, $arguments);
    }

    /**
     * Fire a method.
     * 
     * @param  string $pMethod  The method to fire.
     * @param  object $pObject  The object to fire the method on.
     * @param  array  $pArgs    The arguments to pass to the method.
     * @return mixed            The response.
     */
    public function fireMethod($pMethod, $pController, $pArguments) {
        $unescape = 0;
        
        if (is_string($pMethod)) {
            $ref = new \ReflectionClass($this->controller);
            $refMethod = $ref->getMethod($pMethod);
        } else {
            $refMethod = new \ReflectionFunction($pMethod);
        }
        $metadata = $this->getMethodMetaData($refMethod);

        if (isset($metadata['unescape'])) {
            $unescape = $metadata['unescape'];
        }

        $callable = false;

        if ($pController && is_string($pMethod)) {
            if (!method_exists($pController, $pMethod)) {
                return $this->sendError('MethodNotFoundException', sprintf('Method %s in class %s not found.', $pMethod, get_class($pController)));
            } else {
                $callable = [$pController, $pMethod];
            }
        } elseif (is_callable($pMethod)) {
            $callable = $pMethod;
        }

        if ($callable) {
            try {
                return $this->send(call_user_func_array($callable, $pArguments), $unescape);
            } catch (\Exception $e) {
                return $this->sendException($e);
            }
        }
    }

    /**
     * Describe a route or the whole controller with all routes.
     * 
     * @param  string  $pUri            The uri to describe.
     * @param  boolean $pOnlyRoutes     Whether to only describe the routes.
     * @return array                    The description.
     */
    public function describe($pUri = null, $pOnlyRoutes = false) {
        $definition = [];

        if (!$pOnlyRoutes) {
            $definition['parameters'] = [
                '_method' => [
                    'description' => 'Can be used as HTTP METHOD if the client does not support HTTP methods.', 
                    'type' => 'string',
                    'values' => 'GET, POST, PUT, DELETE, HEAD, OPTIONS, PATCH'
                ],
                '_suppress_status_code' => [
                    'description' => 'Suppress the HTTP status code.', 
                    'type' => 'boolean', 
                    'values' => '1, 0'
                ],
                '_format' => [
                    'description' => 'Format of generated data. Can be added as suffix .json .xml', 
                    'type' => 'string', 
                    'values' => 'json, xml'
                ],
            ];
        }

        $definition['controller'] = [
            'entryPoint' => $this->getTriggerUrl()
        ];

        foreach ($this->routes as $routeUri => $routeMethods) {

            $matches = [];
            if (!$pUri || ($pUri && preg_match('|^'.$routeUri.'$|', $pUri, $matches))) {

                if ($matches) {
                    array_shift($matches);
                }
                $def = [];
                $def['uri'] = $this->getTriggerUrl().'/'.$routeUri;

                foreach ($routeMethods as $method => $phpMethod) {

                    if (is_string($phpMethod)) {
                        $ref = new \ReflectionClass($this->controller);
                        $refMethod = $ref->getMethod($phpMethod);
                    } else {
                        $refMethod = new \ReflectionFunction($phpMethod);
                    }

                    $def['methods'][strtoupper($method)] = $this->getMethodMetaData($refMethod, $matches);

                }
                $definition['controller']['routes'][$routeUri] = $def;
            }
        }

        if (!$pUri) {
            foreach ($this->controllers as $controller) {
                $definition['subController'][$controller->getTriggerUrl()] = $controller->describe(false, true);
            }
        }

        return $definition;
    }

    /**
     * Fetches all meta data informations as params, return type etc.
     * 
     * @param  \ReflectionMethod    $pMethod
     * @param  array                $pRegMatches
     * @return array                The meta data.
     */
    public function getMethodMetaData(\ReflectionFunctionAbstract $pMethod, $pRegMatches = null) {
        $file = $pMethod->getFileName();
        $startLine = $pMethod->getStartLine();

        $fh = fopen($file, 'r');
        if (!$fh)
            return false;

        $lineNr = 1;
        $lines = [];
        while (($buffer = fgets($fh)) !== false) {
            if ($lineNr == $startLine)
                break;
            $lines[$lineNr] = $buffer;
            $lineNr++;
        }
        fclose($fh);

        $phpDoc = '';
        $blockStarted = false;
        while ($line = array_pop($lines)) {

            if ($blockStarted) {
                $phpDoc = $line.$phpDoc;

                //if start comment block: /*
                if (preg_match('/\s*\/\*/', $line)) {
                    break;
                }
                continue;
            } else {
                //we are not in a comment block.
                //if class def, array def or close bracked from fn comes above
                //then we dont have phpdoc
                if (preg_match('/^\s*[a-zA-Z_&\s]*(\$|{|})/', $line)) {
                    break;
                }
            }

            $trimmed = trim($line);
            if ($trimmed == '')
                continue;

            //if end comment block: */
            if (preg_match('/\*\//', $line)) {
                $phpDoc = $line.$phpDoc;
                $blockStarted = true;
                //one line php doc?
                if (preg_match('/\s*\/\*/', $line)) {
                    break;
                }
            }
        }

        $phpDoc = $this->parsePhpDoc($phpDoc);

        $refParams = $pMethod->getParameters();
        $params = [];

        $fillPhpDocParam = !isset($phpDoc['param']);

        foreach ($refParams as $param) {
            $params[$param->getName()] = $param;
            if ($fillPhpDocParam) {
                $phpDoc['param'][] = [
                    'name' => $param->getName(),
                    'type' => $this->declaresArray($param) ? 'array' : 'mixed'
                ];
            }
        }

        $parameters = [];

        if (isset($phpDoc['param'])) {
            if (is_array($phpDoc['param']) && !array_is_list($phpDoc['param']))
                $phpDoc['param'] = [$phpDoc['param']];

            $c = 0;
            foreach ($phpDoc['param'] as $phpDocParam) {
                if (isset($params[$phpDocParam['name']]))
                    $param = $params[$phpDocParam['name']];
                if (!$param)
                    continue;
                $parameter = [
                    'type' => $phpDocParam['type']
                ];

                if ($pRegMatches && is_array($pRegMatches) && $pRegMatches[$c]) {
                    $parameter['fromRegex'] = '$'.($c+1);
                }

                $parameter['required'] = !$param->isOptional();

                if ($param->isDefaultValueAvailable()) {
                    $parameter['default'] = str_replace(["\n", ' '], '', var_export($param->getDefaultValue(), true));
                }
                $parameters[$this->argumentName($phpDocParam['name'])] = $parameter;
                $c++;
            }
        }

        if (!isset($phpDoc['return']))
            $phpDoc['return'] = ['type' => 'mixed'];

        $result = [
            'parameters' => $parameters,
            'return' => $phpDoc['return']
        ];
        
        // Add the remaining phpDoc keys
        $remainingPhpDoc = array_filter($phpDoc, fn($x) => !in_array($x, ['param', 'return']), ARRAY_FILTER_USE_KEY);
        $result = array_merge($result, $remainingPhpDoc);
        
        return $result;
    }

    /**
     * Parse phpDoc string and returns an array of attributes.
     * 
     * @param  string $pString The phpDoc string.
     * @return array           The attribute array.
     */
    public function parsePhpDoc($pString) {
        preg_match('#^/\*\*(.*)\*/#s', trim($pString), $comment);
        
        if (0 === count($comment))
            return [];
        
        $comment = trim($comment[1]);

        preg_match_all('/^\s*\*(.*)/m', $comment, $lines);
        $lines = $lines[1];

        $tags = [];
        $currentTag = '';
        $currentData = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if (substr($line, 0, 1) == '@') {

                if ($currentTag)
                    $tags[$currentTag][] = $currentData;
                else
                    $tags['description'] = $currentData;

                $currentData = '';
                preg_match('/@([a-zA-Z_-]+)/', $line, $match);
                $currentTag = $match[1];
                $line = ltrim(substr($line, strlen($currentTag) + 1));
            }

            $currentData = trim($currentData.' '.$line);

        }
        if ($currentTag)
            $tags[$currentTag][] = $currentData;
        else
            $tags['description'] = $currentData;

        //parse tags using named regular expressions
        $regex = [
            'param'     => '/^(?<type>[a-zA-Z_\\\[\]-]*)\s*\$(?<name>[a-zA-Z_]*)\s*(?<description>.*)/',
            'return'    => '/^(?<type>[a-zA-Z_\\\[\]-]*)\s*(?<description>.*)/',
        ];
        
        foreach ($tags as $tag => &$data) {
            if ($tag === 'description')
                continue;
            foreach ($data as &$item) {
                if (array_key_exists($tag, $regex)) {
                    preg_match($regex[$tag], $item, $match);
                    $item = array_filter($match, fn($x) => !is_numeric($x), ARRAY_FILTER_USE_KEY);
                }
            }
            if (count($data) == 1)
                $data = $data[0];
        }
        
        return $tags;
    }

    /**
     * Set first char to lower case.
     * 
     * @param  string $pName    The name.
     * @return string           The name.
     */
    public function argumentName($pName) {
        if (ctype_lower(substr($pName, 0, 1)) && ctype_upper(substr($pName, 1, 1))) {
            return strtolower(substr($pName, 1, 1)).substr($pName, 2);
        }
        return $pName;
    }

    /**
     * Find and return the route for the URL.
     * 
     * @param  string       $pUri       The URL.
     * @param  string       $pMethod    limit to method.
     * @return array|bool               The route or false if not found.
     */
    public function findRoute($pUri, $pMethod = '_all_') {
        if (isset($this->routes[$pUri][$pMethod]) && $method = $this->routes[$pUri][$pMethod]) {
            return [$method, [], $pMethod, $pUri];
        } elseif ($pMethod != '_all_' && isset($this->routes[$pUri]['_all_']) && $method = $this->routes[$pUri]['_all_']) {
            return [$method, [], $pMethod, $pUri];
        } else {
            //maybe we have a regex uri
            foreach ($this->routes as $routeUri => $routeMethods) {

                if (preg_match('|^'.$routeUri.'$|', $pUri, $matches)) {
                    $arguments = [];
                    if (!isset($routeMethods[$pMethod])) {
                        if (isset($routeMethods['_all_']))
                            $pMethod = '_all_';
                        else
                            continue;
                    }

                    array_shift($matches);
                    foreach ($matches as $match) {
                        $arguments[] = $match;
                    }

                    return [$routeMethods[$pMethod], $arguments, $pMethod, $routeUri];
                }

            }
        }

        return false;
    }
}
