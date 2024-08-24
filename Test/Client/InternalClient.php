<?php

namespace Test\Client;

use RestService\Client;

/**
 * This client does not send any HTTP data, instead it just returns the value.
 * 
 * Good for testing purposes.
 */
class InternalClient extends Client {
    
    /**
     * Sends the actual response.
     * 
     * @param mixed $message The data to process.
     * @param string $httpCode The HTTP code to process.
     * @return string HTTP method of current request.
     */
    public function sendResponse($message, $httpCode = '200', $unescape = 0) {
        $message = array_reverse($message, true);
        $message['status'] = (int)$httpCode;
        $message = array_reverse($message, true);

        $method = $this->getOutputFormatMethod($this->getOutputFormat());

        return $this->$method($message);
    }

}
