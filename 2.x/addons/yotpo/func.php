<?php

if ( !defined('AREA') ) 
{ 
  die('Access denied'); 
}

function fn_yotpo_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses)
{
  if (
      $status_to == "C" &&
      Registry::is_exist('addons.yotpo.yotpo_app_key') && 
      Registry::get('addons.yotpo.yotpo_app_key') != '' && 
      Registry::is_exist('addons.yotpo.yotpo_secret_token') && 
      Registry::get('addons.yotpo.yotpo_secret_token') != '' && 
      fn_check_curl()
      ) 
  {
    $singleMapData = fn_get_single_map_data($order_info);
    $app_key = Registry::get('addons.yotpo.yotpo_app_key');
    $secret_token = Registry::get('addons.yotpo.yotpo_secret_token');
    $token = fn_grant_oauth_access($app_key, $secret_token);
    if(isset($token))
    {
      $singleMapData['platform'] = 'cscart';
      $singleMapData['utoken'] = $token;
      fn_yotpo_make_post_request($singleMapData, YOTPO_API_URL . '/apps/' . $app_key . '/purchases/');   
    }    
  
  }
}

function fn_get_single_map_data($order_info, $auth = null)
{
  
    $data = array();
    $data["order_date"] = date('d-m-Y', $order_info['timestamp']);
    $data["email"] = $order_info['email'];
    $data["customer_name"] = $order_info['firstname'] . ' ' . $order_info['lastname'];
    $data["order_id"] = $order_info['order_id'];
    
    $products = $order_info['items'];
    $products_arr = array();

    $currencies = Registry::get('currencies');
    $currency = isset($order_info['secondary_currency']) ? $currencies[$order_info['secondary_currency']] : $currencies[CART_SECONDARY_CURRENCY];
    $data["currency_iso"] = $currency['currency_code'];    
    foreach ($products as $product) 
    {
      $product_id = is_array($product) ? $product['product_id'] : intval($product); 
      $product_data = array();
      $product_data['url'] = fn_get_product_url($product_id);
      $product_data['name'] = fn_get_product_name($product_id,CART_LANGUAGE,false);
      $product_data['description'] =  db_get_field("SELECT full_description FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $product_id, CART_LANGUAGE);
      if(isset($product_data['description']))
      {
        $product_data['description'] = strip_tags(html_entity_decode($product_data['description'], ENT_NOQUOTES, 'UTF-8'));
      }
      $product_data['image'] = fn_get_product_image_url($product_id);

      $price = is_array($product) ? $product['base_price'] : fn_get_product_price($product_id, 1, $auth);
      $product_data['price'] = fn_format_rate_value($price, 'F', '2', '.', ',', $currency['coefficient']);

      $products_arr[$product_id] = $product_data;
    }
    $data['products'] = $products_arr;
    return $data;
}

function fn_grant_oauth_access($app_key, $secret_token)
{
    $OAuthStorePath = dirname(__FILE__) . '/lib/oauth-php/library/YotpoOAuthStore.php';
    $OAuthRequesterPath = dirname(__FILE__) . '/lib/oauth-php/library/YotpoOAuthRequester.php';

    require_once ($OAuthStorePath);
    require_once ($OAuthRequesterPath);
    $yotpo_options = array( 'consumer_key' => $app_key, 'consumer_secret' => $secret_token, 'client_id' => $app_key, 'client_secret' => $secret_token, 'grant_type' => 'client_credentials' );
    YotpoOAuthStore::instance("2Leg", $yotpo_options);
    try
    {
      $request = new YotpoOAuthRequester(YOTPO_OAUTH_TOKEN_URL, "POST", $yotpo_options);         
      $result = $request->doRequest(0);
      $pregResult = preg_match("/access_token[\W]*[\"'](.*?)[\"']/", $result['body'], $matches);
	  $token = $pregResult == 1 ? $matches[1] : '';
	  return $token != '' ? $token : null;
  }
  catch(YotpoOAuthException2 $e)
  {//Do nothing
    return NULL;
  }
}

function fn_get_product_image_url($product_id)
{
	$image_pair = fn_get_image_pairs($product_id, 'product', 'M', true, true, CART_LANGUAGE);
	if(function_exists('fn_find_valid_image_path')) {
		$valid_image_path = fn_find_valid_image_path($image_pair, 'product',true, CART_LANGUAGE);
		return !empty($valid_image_path) ? 'http://' . Registry::get('config.http_host') . $valid_image_path : null;
	}
	if (!empty($image_pair['image_id']) && !empty($image_pair['icon']) && !empty($image_pair['icon']['image_path'])) {
		return 'http://' . Registry::get('config.http_host') . $image_pair['icon']['image_path'];
	}
}

function fn_get_product_url($product_id)
{
  return fn_url('index.php?dispatch=products.view&product_id=' . $product_id, 'C', 'http', '&', CART_LANGUAGE, '', true);
}

function fn_validate_sign_up_form($name, $email, $password, $passwordConfirm)
{
  if ($email === '')
    return 'Provide valid email address';
  if ($name === '')
    return 'Name is missing';
  if(strlen($password) < 6 || strlen($password) > 128)
    return 'Password must be at least 6 characters';

  if ($password != $passwordConfirm)
    return 'Passwords are not identical';


  return NULL;
}

function fn_yotpo_sign_up($userName, $mail, $password)
{
  $is_mail_valid = fn_check_mail_availability($mail);     
     
  if ($is_mail_valid['status_code'] == 200 && 
	 ($is_mail_valid['json'] == true && $is_mail_valid['response']['available'] == true) || 
	 ($is_mail_valid['json'] == false && preg_match("/available[\W]*(true)/",$is_mail_valid['response']) == 1))
  
  {  
  	$registerResponse = fn_yotpo_register($mail, $userName, $password, 'http://' . Registry::get('config.http_host'));
  	if($registerResponse['status_code'] == 200)
  	{
  		$app_key ='';
  		$secret = '';
  		if ($registerResponse['json'] == true)
  		{
  			$app_key = $registerResponse['response']['app_key'];
  			$secret = $registerResponse['response']['secret'];
  		}
  		else
  		{
  			preg_match("/app_key[\W]*[\"'](.*?)[\"']/",$registerResponse['response'], $matches);
  			$app_key = $matches[1];
  			unset($matches);
  			preg_match("/secret[\W]*[\"'](.*?)[\"']/",$registerResponse['response'], $matches);
  			$secret = $matches[1];
  		}
  		$accountPlatformResponse = fn_yotpo_create_account_platform($app_key, $secret, 'http://' . Registry::get('config.http_host')) ;
  		if($accountPlatformResponse['status_code'] == 200)
  		{
			fn_yotpo_set_settings(array('yotpo_app_key' => $app_key, 'yotpo_secret_token'=> $secret));
  			return NULL;
  		}
  		else {
  			return $response['status_message'];
  		}
  			

  	}
    else
    {        
      return $response['status_message'];        
    } 
  }
  else
  {
	return $is_mail_valid['status_code'] == 200 ? 'This e-mail address is already taken.' : 'An error accourd during registration.';
  }  
}

function fn_check_mail_availability($email)
{
  $data = array();
  $data['model'] = 'user';
  $data['field'] = 'email';
  $data['value'] = $email;
  return fn_yotpo_make_post_request($data, YOTPO_API_URL . '/apps/check_availability');
} 

function fn_yotpo_register($email, $name, $password, $url)
{

  $data = array();
  $user = array();
  $user["email"] = $email;
  $user["display_name"] = $name;
  $user["password"] = $password;
  $user['url'] = $url;
  $data['user'] = $user;
  $data['install_step'] = 'done';
  return fn_yotpo_make_post_request($data, YOTPO_API_URL . '/users.json');
}

function fn_yotpo_create_account_platform($app_key, $secret_token, $shop_url)
{
    $token = fn_grant_oauth_access($app_key, $secret_token);
    if(isset($token))
    {
      $data = array();
      $data['utoken'] = $token;
      $platform_type = array();
      $platform_type['platform_type_id'] = YOTPO_PLATFORM_ID;
      $platform_type['shop_domain'] = $shop_url;
      $data['account_platform'] = $platform_type;
      return fn_yotpo_make_post_request($data, YOTPO_API_URL . '/apps/' . $app_key .'/account_platform');
    }
    return $token;
}

function fn_yotpo_make_post_request($data, $url) 
{
	  list($is_json, $parsed_data) = fn_yotpo_json_or_url_encode($data);    
      $content_type = $is_json ? 'application/json' : 'application/x-www-form-urlencoded'; 
      list (, $result) =  fn_https_request('POST', $url, $parsed_data, null, null, $content_type, null, null, null, null, null, YOTPO_HTTP_REQUEST_TIMEOUT);
      return fn_yotpo_json_decode($result, true);   	
}

function fn_yotpo_get_past_orders($auth)
{
  $from = strtotime("now");
  $to = strtotime('-' . YOTPO_PAST_ORDER_DAYS_LIMIT . ' days');
  $fields = array('order_id', 'firstname', 'lastname', 'email', 'timestamp');

  $condition = "?:orders.timestamp BETWEEN $to AND $from AND ?:orders.status = 'c'";
  $limit = 'LIMIT 0, ' . YOTPO_PAST_ORDER_LIMIT;  
  
  $orders_db_data = db_get_array('SELECT ' . implode(', ', $fields) . " FROM ?:orders WHERE $condition $limit");
  
 
  $orders_products_db = db_get_array("SELECT ?:order_details.product_id,?:order_details.order_id FROM ?:order_details INNER JOIN ?:orders ON ?:orders.order_id = ?:order_details.order_id WHERE $condition");

  $orders_product_by_order_id = array();
  foreach ($orders_products_db as $order_products)
  {
    if(!isset($orders_product_by_order_id[$order_products['order_id']]))
    {
      $orders_product_by_order_id[$order_products['order_id']] = array();
    } 
    $orders_product_by_order_id[$order_products['order_id']][] = $order_products['product_id'];
  }

  foreach ($orders_db_data as &$order)
  {
    $order['items'] = $orders_product_by_order_id[$order['order_id']];
  }
  
  $ordars_map_data = array(); 
  foreach ($orders_db_data as $single_order)
  {
    $ordars_map_data[] = fn_get_single_map_data($single_order ,$auth);
  }
  
  $post_bulk_orders = array_chunk($ordars_map_data, YOTPO_BULK_SIZE);
  $data = array();
  foreach ($post_bulk_orders as $index=>$bulk)
  {
     $data[$index] = array();
     $data[$index]['orders'] = $bulk;
     $data[$index]['platform'] = 'cscart';     
  }     
  return $data;
}

function fn_yotpo_login_link()
{
  $appKey = Registry::get('addons.yotpo.yotpo_app_key');
  $secret = Registry::get('addons.yotpo.yotpo_secret_token'); 
  if(!empty($appKey) && !empty($secret) && $appKey != '' && $secret != '')
  {
    return "<a class='y-href' href='https://api.yotpo.com/users/b2blogin?app_key=" . $appKey ."&secret=" . $secret . "'  target='_blank'>Yotpo Dashboard.</a></div>";
  }
  else
  {
    $signUpHref = "<a class='y-href' href='https://www.yotpo.com/register' target='_blank'>sign up</a>";
    $result = "<p> You have to " .$signUpHref. " first in order to be able to customize Yotpo widget.</p>";
    return $result;
  }
}
function fn_yotpo_get_past_orders_button()
{
	$is_past_orders_sent = db_get_field("SELECT is_sent_past_order FROM ?:addon_yotpo");
	if(is_null($is_past_orders_sent))	
	{
		return '<span class="submit-button">
					<input type="submit" name="dispatch[addons.past_orders]" value="Post past orders">
				</span>';		
	}

}

function fn_yotpo_get_settings($config_name, $cSettings = null, $registry = null) 
{	
	if(!is_null($cSettings)) {
		return $cSettings->get_value($config_name, 'yotpo',CSettings::ADDON_SECTION);
	}
	if(!is_null($registry)) {	
		return $registry[$config_name];
	}
	if (method_exists('CSettings', 'instance')) {
		$cSettings = CSettings::instance();
		return $cSettings->get_value($config_name, 'yotpo',CSettings::ADDON_SECTION);
	}
	else {
		$registry = Registry::get('addons.yotpo');
		return $registry[$config_name];
	}
}

function fn_yotpo_set_settings($data)
{
	if (method_exists('CSettings', 'instance')) {
		$cSettings = CSettings::instance();
		foreach ($data as $key => $value) {
			$cSettings->update_value($key, $value, 'yotpo');
		}
	}
	else {
		$old_options = db_get_field("SELECT options FROM ?:addons WHERE addon = ?s", 'yotpo');
		$old_options = fn_parse_addon_options($old_options);		
		foreach ($data as $key => $value) {
			if (array_key_exists($key, $old_options)) {
				$old_options[$key] = $value;
			}
		}
		$addon_data = array('options' => serialize($old_options));
		db_query("UPDATE ?:addons SET ?u WHERE addon = ?s", $addon_data, 'yotpo');
	}
}

function fn_yotpo_json_or_url_encode($data)
{
	if (function_exists('json_encode'))
		return array(true, json_encode($data));
	elseif (function_exists('fn_to_json'))
		return array(true, fn_to_json($data));
	else 
		return array(false, http_build_query($data));
}

function fn_yotpo_json_decode($data, $assoc = false)
{
	$result = false;
	if (function_exists('json_decode'))
		$result = array(true, json_decode($data, $assoc));
	elseif (function_exists('fn_from_json'))
		$result = array(true, fn_from_json($data, $assoc));


	if ($result)
	{
		$code = isset($result[1]['status']) ? $result[1]['status']['code'] : $result[1]['code'];
		$message = isset($result[1]['status']) ? $result[1]['status']['message'] : $result[1]['message'];
		return array('json' => true, 'status_code' => $code, 'status_message' => $message, 'response' => $result[1]['response']);
	}
	else
	{
		$result = preg_match('/code[\W]*(\d*)/', $data, $matches);
		$status_code = $result == 1 ? $matches[1] : '';
		unset($matches, $result);
		$result = preg_match("/message[\W]*[\"'](.*?)[\"']/", $data, $matches);
		$status_message = $result == 1 ? $matches[1] : '';
		unset($matches, $result);
		$result = preg_match('/response[\W]*({)/', $data, $matches, PREG_OFFSET_CAPTURE);
		$response = '';
		if ($result == 1 && isset($matches[1][1]))
			$response = fn_yotpo_get_string_between_brackets(substr($data, $matches[1][1]));

		return array('json' => false, 'status_code' => $status_code, 'status_message' => $status_message, 'response' => $response);
	}
}

function fn_yotpo_get_string_between_brackets($data)
{
	$count = 0;
	if($data[0] != '{')
		return '';
	for ($position = 0; $position < strlen($data); $position++)
	{
		switch ($data[$position])
		{
			case  '{' :
				$count++;
				break;
			case  '}' :
				$count--;
				break;
				
		}
		if(!$count)
			return substr($data, 0, $position);	
	}
	return '';
}
?>