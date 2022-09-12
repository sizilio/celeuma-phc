<?php

namespace Api;

use Api\Callers\Api;

/**
 * App class
 */
class App {

    protected $object;
    protected Api $api;

    /**
     * @param string $object
     * @param array $headers
     * @param array $payload
     */
    public function __construct (string $object, array $headers = [], array $payload = []) {
        $class = "Api\Objects\\" . ucfirst(strtolower($object)); // Client, Order or Product
        $this->object = new $class($headers, $payload);
        $this->api = new Api();
    }

    /**
     * @return void
     */
    public function run () {
        $exec = $this->object->exec();

        // Callback
        if (is_array($exec)) $this->api->callback(['message' => $exec['message']], $exec['status']);
        else $this->api->callback(['message' => $exec], false);
    }

}