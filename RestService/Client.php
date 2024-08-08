<?php

namespace RestService;

/**
 * This client handles API responses for a given endpoint.
 *
 * It can format the response as JSON, XML, plain text, of a custom format.
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
     * Arguments: (message)
     *
     * @var callable
     */
    protected $customFormat;

    /**
     * List of possible output formats.
     *
     * @var array
     */
    private $outputFormats = array(
        'json' => 'asJSON',
        'xml' => 'asXML',
        'text' => 'asText',
        'custom' => 'customFormat'
    );

    /**
     * List of possible methods.
     * @var array
     */
    public $methods = array('get', 'post', 'put', 'delete', 'head', 'options', 'patch');

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


    private static $statusCodes = array(
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
    );

    /**
     * Create a new client.
     * 
     * @param Server $pServerController The server controller.
     * @return void
     */
    public function __construct($pServerController)
    {
        $this->controller = $pServerController;
        if (isset($_SERVER['PATH_INFO']))
            $this->setURL($_SERVER['PATH_INFO']);

        $this->setupFormats();
    }

    /**
     * Attach client to different controller.
     * 
     * @param Server $pServerController Server controller.
     * @return void
     */
    public function setController($pServerController)
    {
        $this->controller = $pServerController;
    }

    /**
     * Return the currently attached controller.
     * 
     * @return Server $pServerController Server controller.
     */
    public function getController()
    {
        return $this->controller;
    }


    /**
     * Set the custom formatting function/method.
     * Called with arguments: (message)
     *
     * @param  callable $pFn The custom formatting function/method.
     * @return Client   $this The client instance.
     */
    public function setCustomFormat($pFn) {
        $this->customFormat = $pFn;

        return $this;
    }

    /**
     * Returns the current formatting function/method.
     * @return callable $customFormat The check access function/method.
     */
    public function getCustomFormat() {
        return $this->customFormat;
    }

    /**
     * Sends the actual response.
     *
     * @param string $pHttpCode The HTTP code to return.
     * @param $pMessage The data to return.
     * @return void
     */
    public function sendResponse($pMessage, $pHttpCode = '200', $unescape = 0)
    {
        $suppressStatusCode = isset($_GET['_suppress_status_code']) ? $_GET['_suppress_status_code'] : false;
        if ($this->controller->getHttpStatusCodes() &&
            !$suppressStatusCode &&
            php_sapi_name() !== 'cli') {

            $status = self::$statusCodes[intval($pHttpCode)];
            header('HTTP/1.0 ' . ($status ? $pHttpCode . ' ' . $status : $pHttpCode), true, $pHttpCode);
        } elseif (php_sapi_name() !== 'cli') {
            header('HTTP/1.0 200 OK');
        }

        $pMessage = array_reverse($pMessage, true);
        $pMessage['status'] = intval($pHttpCode);
        $pMessage = array_reverse($pMessage, true);

        $method = $this->getOutputFormatMethod($this->getOutputFormat());
        if ($method == 'customFormat' && $this->customFormat == null) {
            echo $this->asJSON($pMessage, $unescape);
        } 
        else if ($method == 'customFormat' && $this->customFormat != null) {
            $args = array($pMessage);
            $result = call_user_func_array($this->getCustomFormat(), $args);
            $this->setContentLength($result);

            echo $result;
        }
        else {
            if ($method == "asJSON" || $method == "json") {
                echo $this->asJSON($pMessage, $unescape);
            }
            else {
                echo $this->$method($pMessage);
            }
        }
        exit;
    }

    /**
     * Returns the current output format method
     * 
     * @param  string $pFormat The output format. 'json', 'xml', 'text', or 'custom'.
     * @return string 'asJSON', 'asXML', 'asText', or 'customFormat'.
     */
    public function getOutputFormatMethod($pFormat)
    {
        return $this->outputFormats[$pFormat];
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
     * @param  string $pMethod 'get', 'post', 'put', 'delete', 'head', 'options', or 'patch'
     * @return Client $this Client instance.
     */
    public function setMethod($pMethod) {
        $this->method = $pMethod;

        return $this;
    }

    /**
     * Set header "Content-Length" from data.
     *
     * @param mixed $pMessage The data to set the header from.
     * @return void
     */
    public function setContentLength($pMessage) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Length: '.strlen($pMessage));
    }

    /**
     * Converts data to pretty JSON.
     *
     * @param mixed $pMessage The data to convert.
     * @return string JSON version of the original data.
     */
    public function asJSON($pMessage, $unescape = 0) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Type: application/json; charset=utf-8');
        

        if ($unescape == 1) {
            if (!is_string($pMessage)) $json = json_encode($pMessage, JSON_UNESCAPED_SLASHES);
            else $json = $pMessage;
        }
        else {
            if (!is_string($pMessage)) $json = json_encode($pMessage);
            else $json = $pMessage;
        }

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '    ';
        $newLine     = "\n";
        $inEscapeMode = false; //if the last char is a valid \ char.
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && !$inEscapeMode) {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
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
     * @param mixed $pMessage The data to convert.
     * @param string $pParentTagName The name of the parent tag. Default is ''.
     * @param int $pDepth The depth of the current tag. Default is 1.
     * @param bool $pHeader Whether to wrap the xml in a header. Default is true.
     * @return string XML version of the original data.
     */
    public function asXML($pMessage, $pParentTagName = '', $pDepth = 1, $pHeader = true) {
        if (is_array($pMessage)) {
            $content = '';

            foreach ($pMessage as $key => $data) {
                $key = is_numeric($key) ? $pParentTagName.'-item' : $key;
                $content .= str_repeat('  ', $pDepth)
                    .'<'.htmlspecialchars($key).'>'.
                    $this->asXml($data, $key, $pDepth+1, false)
                    .'</'.htmlspecialchars($key).">\n";
            }

            $xml = $content;
        } else {
            $xml = htmlspecialchars($pMessage);
        }

        if ($pHeader) {
            $xml = "<?xml version=\"1.0\"?>\n<response>\n$xml</response>\n";
            $this->setContentLength($xml);
        }

        return $xml;
    }

    /**
     * Converts data to text.
     *
     * @param mixed $pMessage The data to convert.
     * @return string JSON version of the original data.
     */
    public function asText($pMessage) {
        if (php_sapi_name() !== 'cli' )
            header('Content-Type: text/plain; charset=utf-8');

        $text = '';
        foreach ($pMessage as $key => $data) {
            $key = is_numeric($key) ? '' : $key.': ';
            $text .= $key.$data."\n";
        }
        $this->setContentLength($text);

        return $text;
    }

    /**
     * Set the current output format.
     *
     * @param  string $pFormat Name of the format.
     * @return Client $this Client instance.
     */
    public function setFormat($pFormat) {
        $this->outputFormat = $pFormat;

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
     * @param  string $pUrl The new endpoint URL.
     * @return Client $this Client instance.
     */
    public function setURL($pUrl) {
        $this->url = $pUrl;

        return $this;
    }

    /**
     * Setup formats.
     *
     * @return Client $this Client instance.
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
        if ($urlFormat == null) {
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
