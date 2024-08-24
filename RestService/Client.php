<?php

namespace RestService;

/**
 * This client handles API responses for a given endpoint.
 * 
 * It can format the response as JSON, XML, plain text, or a custom format.
 */
class Client {
    /**
     * Current output format.
     * 
     * @var string
     */
    private $outputFormat = 'json';

    /**
     * Custom formatting function
     * 
     * Arguments: (string message)
     * 
     * @var callable
     */
    protected $customFormat;

    /**
     * List of possible output formats.
     * 
     * @var array
     */
    private $outputFormats = [
        'json'  => 'asJSON',
        'xml'   => 'asXML',
        'text'  => 'asText',
        'custom' => 'customFormat'
    ];

    /**
     * List of possible methods.
     * @var array
     */
    public $methods = ['get', 'post', 'put', 'delete', 'head', 'options', 'patch'];

    /**
     * Current URL.
     * 
     * @var string
     */
    private $url;

    /**
     * @var Server
     * 
     */
    private $controller;

    /**
     * Custom set http method.
     * 
     * @var string
     */
    private $method;

    private static $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    /**
     * Create a new client.
     * 
     * @param Server $serverController The server controller.
     * @return void
     */
    public function __construct($serverController)
    {
        $this->controller = $serverController;
        if (isset($_SERVER['PATH_INFO']))
            $this->setURL($_SERVER['PATH_INFO']);

        $this->setupFormats();
    }

    /**
     * Attach client to different controller.
     * 
     * @param Server $serverController Server controller.
     * @return void
     */
    public function setController($serverController)
    {
        $this->controller = $serverController;
    }

    /**
     * Return the currently attached controller.
     * 
     * @return Server Server controller.
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Set the custom formatting function/method.
     * 
     * @param  callable $fn The custom formatting function/method. Arguments: (string $message)
     * @return Client       The client instance.
     */
    public function setCustomFormat($fn) {
        $this->customFormat = $fn;

        return $this;
    }

    /**
     * Returns the current formatting function/method.
     * 
     * @return callable The check access function/method.
     */
    public function getCustomFormat() {
        return $this->customFormat;
    }

    /**
     * Sends the actual response.
     * 
     * @param array $message The data to return.
     * @param string $httpCode The HTTP code to return.
     * @return void
     */
    public function sendResponse($message, $httpCode = '200', $unescape = 0)
    {
        $suppressStatusCode = isset($_GET['_suppress_status_code']) ? $_GET['_suppress_status_code'] : false;
        if ($this->controller->getHttpStatusCodes() &&
            !$suppressStatusCode &&
            php_sapi_name() !== 'cli') {

            $status = self::$statusCodes[intval($httpCode)];
            header('HTTP/1.0 ' . ($status ? $httpCode . ' ' . $status : $httpCode), true, $httpCode);
        } elseif (php_sapi_name() !== 'cli') {
            header('HTTP/1.0 200 OK');
        }

        $message = array_reverse($message, true);
        $message['status'] = intval($httpCode);
        $message = array_reverse($message, true);

        $method = $this->getOutputFormatMethod($this->getOutputFormat());
        if ($method == 'customFormat' && $this->customFormat == null) {
            echo $this->asJSON($message, $unescape);
        } else if ($method == 'customFormat' && $this->customFormat != null) {
            $args = [$message];
            $result = call_user_func_array($this->getCustomFormat(), $args);
            $this->setContentLength($result);

            echo $result;
        } else {
            if ($method == "asJSON" || $method == "json") {
                echo $this->asJSON($message, $unescape);
            }
            else {
                echo $this->$method($message);
            }
        }
        exit;
    }

    /**
     * Returns the current output format method
     * 
     * @param  string $format The output format. 'json', 'xml', 'text', or 'custom'.
     * @return string 'asJSON', 'asXML', 'asText', or 'customFormat'.
     */
    public function getOutputFormatMethod($format)
    {
        return $this->outputFormats[$format];
    }

    /**
     * Returns the current output format
     * 
     * @return string 'json', 'xml', 'text', or 'custom'
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    /**
     * Detect the method.
     * 
     * @return string 'get', 'post', 'put', 'delete', 'head', 'options', or 'patch'
     */
    public function getMethod() {
        if ($this->method) {
            return $this->method;
        }

        $method = @$_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];

        if (isset($_GET['_method']))
            $method = $_GET['_method'];
        else if (isset($_POST['_method']))
            $method = $_POST['_method'];

        $method = strtolower($method);

        if (!in_array($method, $this->methods))
            $method = 'get';

        return $method;

    }

    /**
     * Sets a custom http method.
     * 
     * @param  string $method   The request method.
     * @return Client           Client instance.
     */
    public function setMethod($method) {
        $method = strtolower($method);
        if (!in_array($method, $this->methods))
            $method = 'get';
        
        $this->method = $method;
        
        return $this;
    }
    
    /**
     * Set header "Content-Length" from data.
     * 
     * @param mixed $message The data to set the header from.
     * @return void
     */
    public function setContentLength($message) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Length: ' . strlen($message));
    }
    
    /**
     * Converts data to pretty JSON.
     * 
     * @param mixed $message The data to convert.
     * @return string JSON version of the original data.
     */
    public function asJSON($message, $unescape = 0) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Type: application/json; charset=utf-8');
        
        $json = !is_string($message)
                    ? json_encode($message, $unescape ? JSON_UNESCAPED_SLASHES : 0)
                    : $message;
        
        $result      = '';
        $pos         = 0;
        $indentStr   = '    ';
        $newLine     = "\n";
        $inEscapeMode = false; //if the last char is a valid \ char.
        $outOfQuotes = true;

        for ($i = 0, $strLen = strlen($json); $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && !$inEscapeMode) {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            } elseif ($char == ':' && $outOfQuotes) {
                $char .= ' ';
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            if ($char == '\\' && !$inEscapeMode)
                $inEscapeMode = true;
            else
                $inEscapeMode = false;
        }

        $this->setContentLength($result);

        return $result;
    }

    /**
     * Converts data to XML.
     * 
     * @param mixed $message The data to convert.
     * @param string $parentTagName The name of the parent tag. Default is ''.
     * @param int $depth The depth of the current tag. Default is 1.
     * @param bool $header Whether to wrap the xml in a header. Default is true.
     * @return string XML version of the original data.
     */
    public function asXML($message, $parentTagName = '', $depth = 1, $header = true) {
        if (is_array($message)) {
            $content = '';

            foreach ($message as $key => $data) {
                $key = is_numeric($key) ? $parentTagName.'-item' : $key;
                $content .= str_repeat('  ', $depth)
                    .'<'.htmlspecialchars($key).'>'.
                    $this->asXml($data, $key, $depth+1, false)
                    .'</'.htmlspecialchars($key).">\n";
            }

            $xml = $content;
        } else {
            $xml = htmlspecialchars($message);
        }

        if ($header) {
            $xml = "<?xml version=\"1.0\"?>\n<response>\n$xml</response>\n";
            $this->setContentLength($xml);
        }

        return $xml;
    }

    /**
     * Converts data to text.
     * 
     * @param mixed $message The data to convert.
     * @return string JSON version of the original data.
     */
    public function asText($message) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Type: text/plain; charset=utf-8');

        $text = '';
        foreach ($message as $key => $data) {
            $key = is_numeric($key) ? '' : $key.': ';
            $text .= $key.$data."\n";
        }
        $this->setContentLength($text);

        return $text;
    }

    /**
     * Set the current output format.
     * 
     * @param  string $format   Name of the format.
     * @return Client           Client instance.
     */
    public function setFormat($format) {
        $this->outputFormat = $format;

        return $this;
    }

    /**
     * Returns the current endpoint URL.
     * 
     * @return string The current endpoint URL.
     */
    public function getURL() {
        return $this->url;
    }

    /**
     * Set the current endpoint URL.
     * 
     * @param  string $url  The new endpoint URL.
     * @return Client       Client instance.
     */
    public function setURL($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * Setup formats.
     * 
     * @return Client Client instance.
     */
    public function setupFormats() {
        //through HTTP_ACCEPT
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], '*/*') === false) {
            foreach ($this->outputFormats as $formatCode => $formatMethod) {
                if (strpos($_SERVER['HTTP_ACCEPT'], $formatCode) !== false) {
                    $this->outputFormat = $formatCode;
                    break;
                }
            }
        }

        // If URL is null, set to ''
        $urlFormat = $this->getURL();
        if ($urlFormat === null) {
            $urlFormat = '';
        }

        //through uri suffix
        if (preg_match('/\.(\w+)$/i', $urlFormat, $matches)) {
            if (isset($this->outputFormats[$matches[1]])) {
                $this->outputFormat = $matches[1];
                $url = $this->getURL();
                $this->setURL(substr($url, 0, (strlen($this->outputFormat)*-1)-1));
            }
        }

        return $this;
    }

}
