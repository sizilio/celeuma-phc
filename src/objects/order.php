<?php

namespace Api\Objects;

use \Exception;
use Api\Callers\Webhook;

/**
 * Order class
 */
class Order {

    protected Webhook $webhook;
    protected array $headers;
    protected array $payload;

    /**
     * @param array $headers
     * @param array $payload
     */
    public function __construct (array $headers = [], array $payload = []) {
        $this->webhook = new Webhook();
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
        return $this->webhook->call('Order_CreateOrUpdate', $data);
    }

    /**
     * @param array $payload
     * @return array
     */
    private function data (array $payload = []): array {

        // Billing name
        $billingName = !empty($payload['billing']['first_name']) ? $payload['billing']['first_name'] : null;
        $billingName .= !empty($payload['billing']['last_name']) ? ' ' . $payload['billing']['last_name'] : null;

        // Shipping name
        $shippingName = !empty($payload['shipping']['first_name']) ? $payload['shipping']['first_name'] : null;
        $shippingName .= !empty($payload['shipping']['last_name']) ? ' ' . $payload['shipping']['last_name'] : null;
        $shippingName .= !empty($payload['shipping']['company']) ? ' (' . $payload['shipping']['company'] . ')' : null;

        // Data
        return [
            'number' => $payload['id'],
            'status' => $payload['status'],
            'notes' => $payload['customer_note'],
            'client' => $this->client($payload),
            'line_items' => $this->products($payload),
            'billing' => [
                'company' => !empty($payload['billing']['company']) ? $payload['billing']['company'] : $billingName,
                'taxnumber' => '0',
                'address1' => $payload['billing']['address_1'],
                'address2' => $payload['billing']['address_2'],
                'postcode' => $payload['billing']['postcode'],
                'city' => $payload['billing']['city'],
                'country' => $payload['billing']['country'],
                'phone' => $payload['billing']['phone']
            ],
            'shipping' => [
                'name' => $shippingName,
                'address1' => $payload['shipping']['address_1'],
                'address2' => $payload['shipping']['address_2'],
                'postcode' => $payload['shipping']['postcode'],
                'city' => $payload['shipping']['city'],
                'country' => $payload['shipping']['country'],
            ],
            'payment_method' => $payload['payment_method_title'], // payment_method or payment_method_title
            'shipping_method' => $payload['shipping_lines'][0]['method_title'] ?? 'Default',
            'shipping_cost' => (float) $payload['shipping_total'],
            'total' => (float) $payload['total']
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    private function client (array $payload = []): array {
        $client = new Client();
        $customer = $client->getClient($payload['customer_id']);
        $data['id'] = $payload['customer_id'];

        if (!empty($customer) && isset($customer['id'])) {
            $data['name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $data['email'] = $customer['email'];
        }

        return $data;
    }

    /**
     * @param array $payload
     * @return array
     */
    private function products (array $payload = []): array {
        $items = [];

        if (isset($payload['line_items'])) {
            foreach ($payload['line_items'] as $item) {
                $items[] = [
                    'sku' => !empty($item['sku']) ? $item['sku'] : $item['id'],
                    'name' => $item['name'],
                    'supplierid' => 0, // undefined
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                    'tax' => (float) $item['total_tax'],
                    'total' => (float) $item['total']
                ];
            }
        }

        return $items;
    }
}