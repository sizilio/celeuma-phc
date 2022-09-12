<?php

namespace Api\Callers;

use \Exception;

/**
 * Rest class
 */
class Rest {

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
        // create validate...
    }

    /**
     * @param string $url
     * @param array $parameter
     * @return mixed|string
     */
    public function call (string $url, array $parameter = []) {

        // Header
        $headers = [
            "Content-Type: application/json; charset=utf-8"
        ];

        try {
            $response = $this->api->curl($this->urlAuth($url), $headers, !empty($parameter) ? json_encode($parameter) : null); // curl
            if ($response === false) throw new Exception('Curl error.');

            // Log
            $this->api->logger(['url' => $url, 'parameter' => $parameter, 'return' => $response], 'Rest Call');

            // Return
            return json_decode($response, true);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Add authentication infos in url
     * @param string $url
     * @return string
     */
    private function urlAuth (string $url): string {
        return API_REST_URL . $url . "?consumer_key=" . API_REST_KEY . "&consumer_secret=" . API_REST_SECRET;
    }

}