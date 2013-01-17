<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/


if (!defined('AREA')) { die('Access denied'); }

if ($mode == 'view' && !empty($_REQUEST['product_id'])) {
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
	$aaa = get_class($product);
	// fn_logConsole("yotpoImageUrl111",$aaa,false);
	// fn_logConsole("somethins33",json_encode($product) ,false);
	// fn_logConsole("config",json_encode($config) ,false);
	// fn_logConsole("somethins111",json_encode($breadcrumbs) ,false);
	// fn_logConsole("111yotpoBreadCrumbs",json_encode($yotpoBreadCrumbs) ,false);
}



?>