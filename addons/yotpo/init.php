<?php

if ( !defined('AREA') ) { die('Access denied'); }

define('YOTPO_API_URL', 'https://api.yotpo.com');
define('YOTPO_HTTP_REQUEST_TIMEOUT', 30);
define('YOTPO_OAUTH_TOKEN_URL', 'https://api.yotpo.com/oauth/token');
if (!defined('DS')) {
	define('DS', '/');
}
fn_register_hooks(
	'change_order_status'
);
?>