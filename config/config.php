<?php

// Configs
define('DS', '/');
define('API_ROOT', dirname(__DIR__));
define('API_PROD', false); // true = server, false = localhost
define('API_LOG', true); // active log
define('API_CACHE', 3600); // cache life in seconds; 3600 = 1 hour

// PHC
define('API_URL', 'http://dev.superestrela.pt/ws/wscript.asmx'); // url phc
define('API_USER', 'ApiWebStore'); // username phc
define('API_PASS', '#ApiSt0re2022#'); // password phc

// Woocommerce - Webhook
define('API_SECRET', '9892d0eb-4017-4d80-aaed-aedcad3ba4f7'); // uuid - change this into a new api

// Woocommerce - API Rest
define('API_REST_URL', 'https://sizilio.net/wp/wp-json/wc/v3/');
define('API_REST_KEY', 'ck_4b27e61c3d290fd0fd3d92b8f152af4ef426eddf');
define('API_REST_SECRET', 'cs_50f27a35fd710746a7702873eb9d42eeb434d927');