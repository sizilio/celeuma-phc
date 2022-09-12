<?php

namespace Api\Callers;

use \Exception;

/**
 * Api class
 */
class Api {

    /**
     * @param string $url
     * @param array $headers
     * @param string|null $post
     * @return bool|string
     */
    public function curl (string $url, array $headers = [], string $post = null) {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            if (!empty($post)) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            }
            $response = curl_exec($curl);
            curl_close($curl);

            if ($response !== false) return $response;
            else $this->logger(curl_error($curl), 'Curl - Error');
        } catch (Exception $e) {
            $this->logger($e->getMessage(), 'Curl - Exception');
        }

        return false;
    }

    /**
     * @param array $data
     * @param bool $status
     * @param int $code
     * @return void
     */
    public function callback (array $data = [], bool $status = true, int $code = 200): void {

        // Message
        if (empty($data)) $data['message'] = 'Successfully executed.';

        // Http code
        http_response_code($code);

        // Log
        $this->logger($data, 'Return - ' . ($status ? 'Success' : 'Error') . ' (' . $code . ')');

        // Json
        die(json_encode($this->data($status, $data)));
    }

    /**
     * @param bool $status
     * @param array $data
     * @return array
     */
    private function data (bool $status = false, array $data = []) : array {
        return [
            'status' => $status,
            'date' => date('Y-m-d H:i:s'),
            'return' => $data
        ];
    }

    /**
     * @param $text
     * @param string|null $action
     * @return void
     */
    public function logger ($text, string $action = null) : void {
        if (!API_LOG || empty($text)) return;

        $folder = API_ROOT . DS . "logs" . DS . date('Y-m') . DS;
        $file = date('Y-m-d') . ".txt";

        // Log
        $log = !empty($action) ? strtoupper($action) . ": " : null;
        $log .= is_array($text) ? json_encode($text) : $text;
        $log = date('Y-m-d H:i:s') . ": " . (!empty($log) ? $log : "Empty log.") . PHP_EOL . PHP_EOL;

        // Create folder if not exist
        if (!file_exists($folder)) mkdir($folder, 0777, true);

        // Create log text
        if (file_exists($folder . $file)) {
            $existLog = file_get_contents($folder . $file);
            file_put_contents($folder . $file, $existLog . $log);
        } else {
            $openFile = fopen($folder . $file, "w");
            if ($openFile) {
                fwrite($openFile, $log);
                fclose($openFile);
            }
        }
    }

    /**
     * Get cache
     * @param $cache
     * @return string|null
     */
    public function cache ($cache) : ?string {
        $folder = API_ROOT . DS . "logs" . DS . 'cache' . DS;
        $file = str_replace('/', '-', $cache) . ".txt";

        // If exists and in cache, return
        if (file_exists($folder . $file)) {
            if ((time() - filemtime($folder . $file)) <= API_CACHE) {
                return file_get_contents($folder . $file);
            }
        }

        return null;
    }

    /**
     * Write cache
     * @param $cache
     * @param null $content
     */
    public function cacheWrite ($cache, $content = null) : void {
        $folder = API_ROOT . DS . "logs" . DS . 'cache' . DS;
        $file = str_replace('/', '-', $cache) . ".txt";

        // Create folder if not exist
        if (!file_exists($folder)) mkdir($folder, 0777, true);

        // Create cache text
        if (file_exists($folder . $file)) {
            file_put_contents($folder . $file, $content);
        } else {
            $openFile = fopen($folder . $file, "w");
            if ($openFile) {
                fwrite($openFile, $content);
                fclose($openFile);
            }
        }
    }

    /**
     * Delete cache
     * @param $cache
     */
    public function cacheUpdate ($cache, $update = []) : void {
        $folder = API_ROOT . DS . "logs" . DS . 'cache' . DS;
        $file = str_replace('/', '-', $cache) . ".txt";

        // Delete
        if (file_exists($folder . $file)) {
            $existCache = json_decode(file_get_contents($folder . $file), true);
            if (is_array($existCache) && !empty($update)) $existCache[] = $update;

            //file_put_contents($folder . $file, json_encode($existCache));
            unlink($folder . $file);

            // Create new file
            $openFile = fopen($folder . $file, "w");
            if ($openFile) {
                fwrite($openFile, json_encode($existCache));
                fclose($openFile);
            }
        }
    }

}