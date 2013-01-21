<?php


if (!defined('AREA')) { die('Access denied'); }

if ($mode == 'view' && !empty($_REQUEST['product_id']) && Registry::is_exist('addons.yotpo.yotpo_app_key')) {
	$product = $view->get_var('product');
	$breadcrumbs = $view->get_var('breadcrumbs');
	$config = $view->get_var('config');
	$yotpoBreadCrumbs = '';
	if(isset($breadcrumbs))
	{
		foreach ($breadcrumbs as $value) {
			$yotpoBreadCrumbs .= $value['title'].';';

		}
	}
	$yotpoImageUrl = $product['main_pair']['detailed']['http_image_path'];
	$view->assign('yotpoImageUrl', $yotpoImageUrl);
	$view->assign('yotpoBreadCrumbs', $yotpoBreadCrumbs);
	$view->assign('yotpoAppkey', Registry::get('addons.yotpo.yotpo_app_key'));
	$view->assign('yotpoProductImageUrl', fn_get_product_image_url($product['product_id']));
	$view->assign('yotpoProductUrl', fn_get_product_url($product['product_id']));
	$view->assign('yotpoLanguage', CART_LANGUAGE);
	// fn_logConsole("yotpoImageUrl111",$aaa,false);
	// fn_logConsole("somethins33",json_encode($product) ,false);
	// fn_logConsole("config",json_encode($config) ,false);
	// fn_logConsole("somethins111",json_encode($breadcrumbs) ,false);
	// fn_logConsole("111yotpoBreadCrumbs",json_encode($yotpoBreadCrumbs) ,false);
}



?>