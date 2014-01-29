<?php

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

define('YOTPO_API_URL', 'https://api.yotpo.com');
define('YOTPO_HTTP_REQUEST_TIMEOUT', 30);
define('YOTPO_OAUTH_TOKEN_URL', 'https://api.yotpo.com/oauth/token');
define('YOTPO_PLATFORM_ID', 9);
define("YOTPO_PAST_ORDER_LIMIT", 10000);
define("YOTPO_PAST_ORDER_DAYS_LIMIT", 30);
define("YOTPO_BULK_SIZE", 500);
fn_register_hooks(
    'change_order_status'
);
?>