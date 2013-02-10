<?php


if (!defined('AREA')) { die('Access denied'); }

if ($mode == 'view' && !empty($_REQUEST['product_id']) && Registry::is_exist('addons.yotpo.yotpo_app_key')) {
	$product = $view->get_var('product');
	$breadcrumbs = $view->get_var('breadcrumbs');
	$yotpoBreadCrumbs = '';
	if(isset($breadcrumbs))
	{
		foreach ($breadcrumbs as $value) {
			$yotpoBreadCrumbs .= $value['title'].';';

		}
	}
	$view->assign('yotpoBreadCrumbs', $yotpoBreadCrumbs);
	$view->assign('yotpoAppkey', Registry::get('addons.yotpo.yotpo_app_key'));
	$view->assign('yotpoProductImageUrl', fn_get_product_image_url($product['product_id']));
	$view->assign('yotpoProductUrl', fn_get_product_url($product['product_id']));
	$view->assign('yotpoLanguage', CART_LANGUAGE);
	$view->assign('yotpoLanguage', Registry::get('addons.yotpo.yotpo_widget_language'));
}



?>