<?php

if ( !defined('AREA') ) { die('Access denied'); }

/**
 * Returns array of Live Help options from registry
 * @return type
 */
function fn_get_widget_data()
{
	$array = array(
	    "foo" => "bar",
	    "bar" => "foo",
	);
	// fn_logConsole("somethins",json_encode($product) ,false);
	
	$array = array(
	    "foo" => "bar",
	);
	return $array;
}

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

    $data['platform'] = 'prestashop';

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
    fn_declare_consts();
    fn_http_request('POST', YOTPO_API_URL . '/apps/' . $app_key . "/purchases/", $data, NULL, NULL, HTTP_REQUEST_TIMEOUT);
  }
}


function fn_declare_consts()
{ 
  if (!defined('YOTPO_API_URL')) {
    define('YOTPO_API_URL', "https://api.yotpo.com");
  }
  if (!defined('HTTP_REQUEST_TIMEOUT')) {
    define('HTTP_REQUEST_TIMEOUT', 30);
  }
  if (!defined('YOTPO_OAUTH_TOKEN_URL')) {
    define('YOTPO_OAUTH_TOKEN_URL', "https://api.yotpo.com/oauth/token");
  }
  if (!defined('DS')) {
    define('DS', '/');
  }  
}

function fn_grant_oauth_access($app_key, $secret_token)
{
    fn_declare_consts();
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
  $valid_image_path = fn_find_valid_image_path($image_pair, 'product');
  return !empty($valid_image_path) ? 'http://' . Registry::get('config.http_host') . $valid_image_path : 'http://' . Registry::get('config.http_location') . '/images/no_image.gif';
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

function fn_yotpo_sign_up($userName, $mail, $password, $appKeyObjectId, $secretKeyObjectId)
{
  fn_declare_consts();
  $is_mail_valid = json_decode(fn_check_mail_availability($mail), true);     
     
  if($is_mail_valid['status']['code'] == 200 && $is_mail_valid['response']['available'] == true)
  {  
    $response = json_decode(fn_yotpo_register($mail, $userName, $password, Registry::get('config.current_location')), true);
    if($response['status']['code'] == 200)
    {
      $accountPlatformResponse = json_decode(fn_yotpo_create_account_platform($response['response']['app_key'], $response['response']['secret'], Registry::get('config.current_location')), true);        
      if($accountPlatformResponse['status']['code'] == 200)
      {
        CSettings::instance()->update_value_by_id($appKeyObjectId, $response['response']['app_key']);
        CSettings::instance()->update_value_by_id($secretKeyObjectId, $response['response']['secret']);
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
  list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/apps/check_availability', $data, NULL, NULL, HTTP_REQUEST_TIMEOUT);
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
  list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/users.json', $data, NULL, NULL, HTTP_REQUEST_TIMEOUT);
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
      $platform_type['platform_type_id'] = 9;
      $platform_type['shop_domain'] = $shop_url;
      $data['account_platform'] = $platform_type;
      list (, $result) =  fn_http_request('POST', YOTPO_API_URL . '/apps/' . $app_key .'/account_platform', $data, NULL, NULL, HTTP_REQUEST_TIMEOUT);
      return $result;
    }
    return $token;
}

/**
* Logs messages/variables/data to browser console from within php
*
* @param $name: message to be shown for optional data/vars
* @param $data: variable (scalar/mixed) arrays/objects, etc to be logged
* @param $jsEval: whether to apply JS eval() to arrays/objects
*
* @return none
* @author Sarfraz
*/
function fn_logConsole($name, $data = NULL, $jsEval = FALSE)
{
  if (! $name) return false;

  $isevaled = false;
  $type = ($data || gettype($data)) ? 'Type: ' . gettype($data) : '';

  if ($jsEval && (is_array($data) || is_object($data)))
  {
       $data = 'eval(' . preg_replace('#[\s\r\n\t\0\x0B]+#', '', json_encode($data)) . ')';
       $isevaled = true;
  }
  else
  {
       $data = json_encode($data);
  }

  # sanitalize
  $data = $data ? $data : '';
  $search_array = array("#'#", '#""#', "#''#", "#\n#", "#\r\n#");
  $replace_array = array('"', '', '', '\\n', '\\n');
  $data = preg_replace($search_array,  $replace_array, $data);
  $data = ltrim(rtrim($data, '"'), '"');
  $data = $isevaled ? $data : ($data[0] === "'") ? $data : "'" . $data . "'";

$js = <<<JSCODE
\n<script>
     // fallback - to deal with IE (or browsers that don't have console)
     if (! window.console) console = {};
     console.log = console.log || function(name, data){};
     // end of fallback

     console.log('$name');
     console.log('------------------------------------------');
     console.log('$type');
     console.log($data);
     console.log('\\n');
</script>
JSCODE;

          echo $js;
     } # end logConsole
?>