<?php
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
if($mode == 'complete' && Registry::isExist('addons.yotpo.yotpo_app_key') && !empty($_REQUEST['order_id'])) {
    $app_key = Registry::get('addons.yotpo.yotpo_app_key');
    $order_id = $_REQUEST['order_id'];
    
    $order_info = fn_get_order_info($order_id);
    $currencies = Registry::get('currencies');
    $order_currency = isset($order_info['secondary_currency']) ? $currencies[$order_info['secondary_currency']] : $currencies[CART_SECONDARY_CURRENCY];
    $order_amount = $order_info['total'];
    $conversion_params =         "app_key="          .$app_key.
                                 "&order_id="        .$order_id.
                                 "&order_amount="    .$order_amount.
                                 "&order_currency="  .$order_currency['currency_code'];
    $conversion_url = "https://api.yotpo.com/conversion_tracking.gif?$conversion_params";
    Registry::get('view')->assign('yotpoConversionUrl', $conversion_url);
}