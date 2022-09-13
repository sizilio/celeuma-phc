<?php

namespace Api\Objects;

use Api\Callers\Api;
use Api\Callers\Rest;
use Api\Callers\Webhook;
use \Exception;

/**
 * Product class
 */
class Product {

    protected Api $api;
    protected Rest $rest;
    protected Webhook $webhook;
    protected array $headers;
    protected array $payload;

    /**
     * @param array $headers
     * @param array $payload
     */
    public function __construct (array $headers = [], array $payload = []) {
        $this->api = new Api();
        $this->webhook = new Webhook();
        $this->rest = new Rest();
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * @return array|string
     */
    public function exec () {

        // Auth
        $this->rest->auth($this->headers, $this->payload);

        // Update all products, or one if empty payload
        // recommended to run only once a day
        if (empty($this->payload)) {
            $exec = 'Error receiving products.';
            $products = $this->webhook->call('Product_GetAll');
            if (is_array($products) && !empty($products))
                foreach ($products as $product)
                    $exec = $this->execProduct($product);
        } else {
            $exec = $this->execProduct($this->payload);
        }

        // Return
        return $exec;
    }

    /**
     * @return array|mixed|string
     */
    public function execProduct ($payload) {

        // Check exist client
        // if there is no ID, but there is SKU, it will search for products to identify the ID
        $product = [];
        if (!isset($payload['id']) && isset($payload['sku']))
            $product = $this->getProductsCache('products', 'sku', $payload['sku']);
        elseif (isset($this->payload['id']))
            $product = $this->getProductsCache('products', 'id' , $payload['id']);

        // Correct data
        $data = $this->data($payload);

        // Call to add or edit
        if (empty($product)) $call = $this->rest->call("products", $data);
        else $call = $this->rest->call("products/" . $product['id'], $data);

        // Return
        if (is_array($call))
            return ['status' => !isset($call['code']), 'message' => isset($call['code']) ? $call['message'] : 'Product created/edited successfully.'];
        else
            return $call;
    }

    /**
     * Fix attribute data
     * @param array $data
     * @return array
     */
    private function data (array $data = []) : array {

        // Attributes
        $attributes = $data['atributes'][0] ?? null;
        $categoryName = $attributes['Familia'] ?? null;
        $subcategoryName = $attributes['SubFamilia'] ?? null;
        $brandName = $attributes['Marca'] ?? null;
        $groupName = $attributes['Grupo'] ?? null;

        // Category
        if (!empty($categoryName)) {
            $category = $this->getProductsCache('products/categories', 'name', $categoryName);
            if (empty($category)) {
                $category = $this->rest->call('products/categories', ['name' => $categoryName]);
                if (empty($subcategoryName)) $this->api->cacheUpdate('products/categories', $category);
            }
            $data['categories'][] = ['id' => $category['id']];
        }

        // Subcategory
        if (!empty($subcategoryName)) {
            $subcategory = $this->getProductsCache('products/categories', 'name', $subcategoryName);
            if (empty($subcategory)) {
                $subcategory = $this->rest->call('products/categories', [
                    'name' => $subcategoryName,
                    'parent' => !empty($category) ? $category['id'] : 0
                ]);
                $this->api->cacheUpdate('products/categories', $subcategory);
            }
            $data['categories'][] = ['id' => $subcategory['id']];
        }

        // Brand
        if (!empty($brandName)) {
            $brand = $this->getProductsCache('products/attributes', 'name', 'Marca');
            if (empty($brand)) {
                $brand = $this->rest->call('products/attributes', ['name' => 'Marca']);
                $this->api->cacheUpdate('products/attributes', $brand);
            }
            $data['attributes'][] = [
                'id' => $brand['id'],
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => [$brandName]
            ];
        }

        // Group
        if (!empty($groupName)) {
            $group = $this->getProductsCache('products/attributes', 'name', 'Grupo');
            if (empty($group)) {
                $group = $this->rest->call('products/attributes', ['name' => 'Grupo']);
                $this->api->cacheUpdate('products/attributes', $group);
            }
            $data['attributes'][] = [
                'id' => $group['id'],
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => [$groupName]
            ];
        }

        // Stock
        if (isset($data['stock_quantity']) && !empty($data['stock_quantity'])) $data['manage_stock'] = true;

        // Images
        // remove this code after!!!
        if (isset($data['images']) && !empty($data['images'])) unset($data['images']);

        // Return
        //unset($data['atributes']);
        return $data;
    }

    /**
     * @param $cache
     * @param $key
     * @param $value
     * @return array
     */
    private function getProductsCache ($type, $key, $value) {
        $cache = $this->api->cache($type);
        $cache = empty($cache) ? $this->getProductsInfos($type) : json_decode($cache, true);

        // Search info
        if (!empty($cache)) {
            foreach ($cache as $info)
                if (isset($info[$key]) && $info[$key] == $value)
                    return $info;
        }

        return [];
    }

    /**
     * Get all products
     * @return array|mixed
     */
    private function getProductsInfos ($type) : array {

        // Cache
        $cache = $this->api->cache($type);
        if (empty($cache)) {
            $cache = $this->rest->call($type); // get all infos (products, categorias...)
            if (!isset($products['code'])) $this->api->cacheWrite($type, json_encode($cache)); // write cache
            else return [];
        }

        // Return
        return is_string($cache) ? json_decode($cache) : $cache;
    }

    /**
     * @param int $id
     * @return array|string
     *
    public function getProduct (int $id) {
        if (empty($id)) return [];
        $call = $this->rest->call("products/" . $id);

        if (is_array($call) && isset($call['code'])) return [];
        else return $call;
    }/**/

}