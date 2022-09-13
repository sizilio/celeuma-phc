<?php

namespace Api\Callers;

use \Exception;
use \SimpleXMLElement;

/**
 * Webhook class
 */
class Webhook {

    protected Api $api;

    /**
     * construct
     */
    public function __construct() {
        $this->api = new Api();
    }

    /**
     * @param array $headers
     * @param array $data
     * @return void
     */
    public function auth (array $headers = [], array $data = []) {

        // Valid secret
        $hash = $headers['x-wc-webhook-signature'] ?? null;
        $checkHash = base64_encode(hash_hmac('sha256', json_encode($data), API_SECRET, true));

        // Check
        if ($hash !== $checkHash) {
            $this->api->logger(['hash' => $hash, 'checkHash' => $checkHash], 'Authentication');
            $this->api->callback(['message' => 'Authentication failed.'], 403);
        }
    }

    /**
     * @param string|null $action
     * @param array $parameter
     * @return mixed
     * @throws Exception
     */
    public function call (string $action = null, array $parameter = []) {
        $xml = $this->callXml($action, $parameter);

        // Header
        $headers = [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-length: " . strlen($xml),
            "SOAPAction: " . $action
        ];

        try {
            $response = $this->api->curl(API_URL, $headers, $xml); // curl
            if ($response === false) throw new Exception('Curl error.');

            $return = $this->callReturn($response); // edit return

            // Log
            //$this->logger($xml, 'Call - Xml');
            $this->api->logger(['parameter' => $parameter, 'return' => $return], 'Webhook Call' . (!empty($action) ? ' - ' . $action : null));

            // Return
            if (is_array($return) && isset($return['Status'])) return ['status' => (bool) $return['Status'], 'message' => "PHC: " . $return['Msg']];
            else return $return;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string|null $action
     * @param array $parameter
     * @return string
     */
    private function callXml (string $action = null, array $parameter = []): string {
        return '<?xml version="1.0" encoding="utf-8"?>' .
        '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">' .
            '<soap12:Body>' .
                '<RunCode xmlns="http://www.phc.pt/">' . 
                    '<userName>' . API_USER . '</userName>' .
                    '<password>' . API_PASS . '</password>' . 
                    '<code>' . $action . '</code>' . 
                    '<parameter>' . (!empty($parameter) && is_array($parameter) ? json_encode($parameter) : null) . '</parameter>' .
                '</RunCode>' .
            '</soap12:Body>' . 
        '</soap12:Envelope>';
    }

    /**
     * @param string $response
     * @return array|string
     * @throws Exception
     */
    private function callReturn (string $response) {
        $return = [];
        $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
        $xml = new SimpleXMLElement($response);
        $body = $xml->xpath('//soapBody')[0];
        $body = json_decode(json_encode($body), true);

        if (isset($body['RunCodeResponse']['RunCodeResult'])) {
            $json = @json_decode($body['RunCodeResponse']['RunCodeResult'], true);
            if (!empty($json)) $return = $json;
            else $return = $body['RunCodeResponse']['RunCodeResult'];
        }

        return $return;
    }

}