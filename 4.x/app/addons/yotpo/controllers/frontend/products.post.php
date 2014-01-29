<?php
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view' && !empty($_REQUEST['product_id']) && Registry::isExist('addons.yotpo.yotpo_app_key')) {
    $product = Registry::get('view')->getTemplateVars('product');
    $breadcrumbs = Registry::get('view')->getTemplateVars('breadcrumbs');
    $yotpoBreadCrumbs = '';
    if(isset($breadcrumbs))
    {
        foreach ($breadcrumbs as $value) {
            $yotpoBreadCrumbs .= $value['title'].';';

        }
    }
    Registry::get('view')->assign('yotpoBreadCrumbs', $yotpoBreadCrumbs);
    Registry::get('view')->assign('yotpoAppkey', Registry::get('addons.yotpo.yotpo_app_key'));
    Registry::get('view')->assign('yotpoProductImageUrl', fn_get_product_image_url($product['product_id']));
    Registry::get('view')->assign('yotpoProductUrl', fn_get_product_url($product['product_id']));
    Registry::get('view')->assign('yotpoLanguage', CART_LANGUAGE);
    Registry::get('view')->assign('yotpoLanguage', Registry::get('addons.yotpo.yotpo_widget_language'));
}



?>