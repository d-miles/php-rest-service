<?php

namespace RestService;

/**
 * OpenAPI Client
 */
class OpenApiClient extends Client {
    public function __construct($controller) {
        parent::__construct($controller);
        $this->setFormat('custom');
        $this->setCustomFormat($this->asHTML(...));
    }
    
    /**
     * Unwraps the Server controller response. 
     * 
     * The Server response format has a data key, however the OpenAPI specification has its
     * own JSON schema. This method will simply return the Server's raw data object to be
     * serialized by the format method.
     * 
     * @param array $response Server controller response
     * @return string
     */
    private function unwrapData($response) {
        // Unwrap the data object so that it can be sent plainly
        if (array_key_exists('data', $response)) {
            $response = $response['data'];
        }
        
        return $response;
    }
    
    public function asJSON($message, $unescape = false) {
        $message = $this->unwrapData($message);
        
        if (php_sapi_name() !== 'cli')
            header('Content-Type: application/json; charset=utf-8');
        
        $result = parent::asJSON($message);
        $this->setContentLength($result);
        
        return $result;
    }
    
    public function asHTML($message) {
        if (php_sapi_name() !== 'cli')
            header('Content-Type: text/html; charset=utf-8');
        
        $html = $message['data'];
        
        if (is_array($html)) {
            $html = $this->asJSON($html);
        }
        
        $this->setContentLength($html);
        return $html;
    }
    
    public function asXML($message, $parentTag = '', $depth = 1, $header = true) {
        $message = $this->unwrapData($message);
        
        $xml = parent::asXML($message);
        
        return $xml;
    }
}
