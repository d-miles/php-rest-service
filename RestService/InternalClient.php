<?php

namespace RestService;

/**
 * This client does not send any HTTP data, instead it just returns the value.
 * 
 * Good for testing purposes.
 */
class InternalClient extends Client {
    /**
     * Sends the actual response.
     * 
     * @param mixed $pMessage The data to process.
     * @param string $pHttpCode The HTTP code to process.
     * @return string HTTP method of current request.
     */
    public function sendResponse($pMessage, $pHttpCode = '200', $unescape = 0) {
        $pMessage = array_reverse($pMessage, true);
        $pMessage['status'] = $pHttpCode+0;
        $pMessage = array_reverse($pMessage, true);

        $method = $this->getOutputFormatMethod($this->getOutputFormat());

        return $this->$method($pMessage);
    }

}
