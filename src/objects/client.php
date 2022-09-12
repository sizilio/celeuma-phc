<?php

namespace Api\Objects;

use \Exception;
use Api\Callers\Webhook;
use Api\Callers\Api;
use Api\Callers\Rest;

/**
 * Client class
 */
class Client {

    protected Webhook $webhook;
    protected Api $api;
    protected Rest $rest;
    protected array $headers;
    protected array $payload;

    /**
     * @param array $headers
     * @param array $payload
     */
    public function __construct (array $headers = [], array $payload = []) {
        $this->webhook = new Webhook();
        $this->api = new Api();
        $this->rest = new Rest();
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * @return array|mixed|string
     * @throws Exception
     */
    public function exec () {

        // Auth
        $this->webhook->auth($this->headers, $this->payload);

        // Data
        $data = $this->data($this->payload);

        // Call
        return $this->webhook->call('Client_CreateOrUpdate', $data);
    }

    /**
     * @param int $id
     * @return array|string
     */
    public function getClient (int $id) {
        if (empty($id)) return [];
        $call = $this->rest->call("customers/" . $id);

        if (is_array($call) && isset($call['code'])) return [];
        else return $call;
    }

    /**
     * @param array $payload
     * @return array
     */
    private function data (array $payload = []): array {

        // Name
        $name = !empty($payload['first_name']) ? $payload['first_name'] : null;
        $name .= !empty($payload['last_name']) ? ' ' . $payload['last_name'] : null;

        // Billing name
        $billingName = !empty($payload['billing']['first_name']) ? $payload['billing']['first_name'] : null;
        $billingName .= !empty($payload['billing']['last_name']) ? ' ' . $payload['billing']['last_name'] : null;

        // Shipping name
        $shippingName = !empty($payload['shipping']['first_name']) ? $payload['shipping']['first_name'] : null;
        $shippingName .= !empty($payload['shipping']['last_name']) ? ' ' . $payload['shipping']['last_name'] : null;

        // Data
        return [
            'id' => $payload['id'],
            'name' => $name,
            'email' => $payload['email'],
            'role' => 'customer',
            'billing' => [
                'company' => !empty($payload['billing']['company']) ? $payload['billing']['company'] : (!empty($billingName) ? $billingName : $name),
                'taxnumber' => '0',
                'address1' => $payload['billing']['address_1'],
                'address2' => $payload['billing']['address_2'],
                'postcode' => $payload['billing']['postcode'],
                'city' => $payload['billing']['city'],
                'country' => $payload['billing']['country'],
                'phone' => $payload['billing']['phone']
            ],
            'shipping' => [
                'name' => !empty($shippingName) ? $shippingName : $name,
                'address1' => $payload['shipping']['address_1'],
                'address2' => $payload['shipping']['address_2'],
                'postcode' => $payload['shipping']['postcode'],
                'city' => $payload['shipping']['city'],
                'country' => $payload['shipping']['country']
            ]
        ];
    }

}