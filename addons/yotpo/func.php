<?php

if ( !defined('AREA') ) { die('Access denied'); }

function fn_yotpo_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses)
{
  if (
      $status_to == "C" &&
      Registry::is_exist('addons.yotpo.yotpo_mail_after_purchase') && 
      Registry::get('addons.yotpo.yotpo_mail_after_purchase') == true && 
      Registry::is_exist('addons.yotpo.yotpo_app_key') && 
      Registry::get('addons.yotpo.yotpo_app_key') != '' && 
      Registry::is_exist('addons.yotpo.yotpo_secret_token') && 
      Registry::get('addons.yotpo.yotpo_secret_token') != '' && 
      fn_check_curl()
      ) 
  {
    fn_yotpo_make_map_request(Registry::get('addons.yotpo.yotpo_app_key'), Registry::get('addons.yotpo.yotpo_secret_token'), $order_info);
  }
}

// public function fn_yotpo_make_map_request($params, $app_key, $secret_token, $context)
function fn_yotpo_make_map_request($app_key, $secret_token, $order_info)
{
  $token = fn_grant_oauth_access($app_key, $secret_token);
  if(isset($token))
  {

    $data = array();
    $data["order_date"] = date('d-m-Y', $order_info['timestamp']);
    $data['utoken'] = $token;
    $data["email"] = $order_info['email'];
    $data["customer_name"] = $order_info['firstname'] . ' ' . $order_info['lastname'];
    $data["order_id"] = $order_info['order_id'];

    $data['platform'] = 'cscart';

    $products = $order_info['items'];
    $products_arr = array();

    $currencies = Registry::get('currencies');
    $currency = $currencies[$order_info['secondary_currency']];

    $data["currency_iso"] = $currencies[$order_info['secondary_currency']]['currency_code'];
    foreach ($products as $product) 
    {
      $product_data = array();
      $product_data['url'] = fn_get_product_url($product['product_id']);
      $product_data['name'] = fn_get_product_name($product['product_id'],CART_LANGUAGE,false);
      $product_data['description'] =  db_get_field("SELECT full_description FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $product['product_id'], CART_LANGUAGE);
      if(isset($product_data['description']))
      {
        $product_data['description'] = strip_tags(html_entity_decode($product_data['description'], ENT_NOQUOTES, 'UTF-8'));
      }
      $product_data['image'] = fn_get_product_image_url($product['product_id']);
      
      $product_data['price'] = fn_format_rate_value($product['base_price'], 'F', '2', '.', ',', $currency['coefficient']);

      $products_arr[$product['product_id']] = $product_data;
    }

    $data['products'] = $products_arr;
    fn_http_request('POST', YOTPO_API_URL . '/apps/' . $app_key . "/purchases/", $data, NULL, NULL, YOTPO_HTTP_REQUEST_TIMEOUT);
  }
}

function fn_grant_oauth_access($app_key, $secret_token)
{
    $OAuthStorePath = dirname(__FILE__) . DS . 'lib'. DS .'oauth-php' . DS . 'library' . DS . 'OAuthStore.php';
    $OAuthRequesterPath = dirname(__FILE__) . DS . 'lib'. DS .'oauth-php' . DS . 'library' . DS . 'OAuthRequester.php';

    require_once ($OAuthStorePath);
    require_once ($OAuthRequesterPath);
    $yotpo_options = array( 'consumer_key' => $app_key, 'consumer_secret' => $secret_token, 'client_id' => $app_key, 'client_secret' => $secret_token, 'grant_type' => 'client_credentials' );
    OAuthStore::instance("2Leg", $yotpo_options);
    try
    {
      $request = new OAuthRequester(YOTPO_OAUTH_TOKEN_URL, "POST", $yotpo_options);         
      $result = $request->doRequest(0);
      $tokenParams = json_decode($result['body'], true);

      if(isset($tokenParams['access_token']))
        return $tokenParams['access_token'];
      else
        return NULL;
  }
  catch(OAuthException2 $e)
  {//Do nothing
    return NULL;
  }
}

function fn_get_product_image_url($product_id)
{
  $image_pair = fn_get_image_pairs($product_id, 'product', 'M', true, true, CART_LANGUAGE);
  $valid_image_path = fn_find_valid_image_path($image_pair, 'product',true, CART_LANGUAGE);
  return !empty($valid_image_path) ? 'http://' . Registry::get('config.http_host') . $valid_image_path : NULL;
}

function fn_get_product_url($product_id)
{
  return fn_url('index.php?dispatch=products.view&product_id=' . $product_id, 'C', 'http', '&', CART_LANGUAGE, '', true);
}

function fn_validate_sign_up_form($userName, $mail, $password, $passwordConfirm)
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
  $is_mail_valid = json_decode(fn_check_mail_availability($mail), true);     
     
  if($is_mail_valid['status']['code'] == 200 && $is_mail_valid['response']['available'] == true)
  {  
    $response = json_decode(fn_yotpo_register($mail, $userName, $password, 'http://' . Registry::get('config.http_host')), true);
    if($response['status']['code'] == 200)
    {
      $accountPlatformResponse = json_decode(fn_yotpo_create_account_platform($response['response']['app_key'], $response['response']['secret'], 'http://' . Registry::get('config.http_host')), true);        
      if($accountPlatformResponse['status']['code'] == 200)
      {
        $cSettings = CSettings::instance();
        $cSettings->update_value('yotpo_app_key', $response['response']['app_key'], 'yotpo');
        $cSettings->update_value('yotpo_secret_token', $response['response']['secret'], 'yotpo');
        return NULL;  
      }
      else
        return $response['status']['message'];  
      
    } 
    else
    {        
      return $response['status']['message'];        
    } 
  }
  else
  {
    if($is_mail_valid['status']['code'] == 200 )
      return 'This mail is allready taken.';
    else
      return 'An error accourd during registration.';
  }  
}

function fn_check_mail_availability($email)
{
  $data = array();
  $data['model'] = 'user';
  $data['field'] = 'email';
  $data['value'] = $email;
  list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/apps/check_availability', $data, NULL, NULL, YOTPO_HTTP_REQUEST_TIMEOUT);
  return $result;
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
  list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/users.json', $data, NULL, NULL, YOTPO_HTTP_REQUEST_TIMEOUT);
  return $result;
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
      list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/apps/' . $app_key .'/account_platform', $data, NULL, NULL, YOTPO_HTTP_REQUEST_TIMEOUT);
      return $result;
    }
    return $token;
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
    $result = "<p> You have to " .$signUpHref. " first in order to be able to costumize Yotpo widget.</p>";
    return $result;
  }
}
?>