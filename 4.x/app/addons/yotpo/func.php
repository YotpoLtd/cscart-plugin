<?php
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Http;

if ( !defined('BOOTSTRAP') ) 
{ 
  die('Access denied'); 
}

function fn_yotpo_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses)
{
  if (
      $status_to == "C" &&
      Registry::isExist('addons.yotpo.yotpo_app_key') && 
      Registry::get('addons.yotpo.yotpo_app_key') != '' && 
      Registry::isExist('addons.yotpo.yotpo_secret_token') && 
      Registry::get('addons.yotpo.yotpo_secret_token') != '' && 
      function_exists('curl_init')
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
      Http::post(YOTPO_API_URL . '/apps/' . $app_key . "/purchases/", json_encode($singleMapData), array('headers' => array('Content-Type: application/json'), 'timeout' => YOTPO_HTTP_REQUEST_TIMEOUT));
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
    
    $products = $order_info['products'];
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
      $tokenParams = json_decode($result['body'], true);

      if(isset($tokenParams['access_token']))
        return $tokenParams['access_token'];
      else
        return NULL;
  }
  catch(YotpoOAuthException2 $e)
  {//Do nothing
    return NULL;
  }
}

function fn_get_product_image_url($product_id)
{
  $image_pair = fn_get_image_pairs($product_id, 'product', 'M', true, true, CART_LANGUAGE);
  return !empty($image_pair['detailed']['image_path']) ? $image_pair['detailed']['image_path'] : NULL;
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
  $is_mail_valid = json_decode(fn_check_mail_availability($mail), true);
     
  if($is_mail_valid['status']['code'] == 200 && $is_mail_valid['response']['available'] == true)
  {  
    $response = json_decode(fn_yotpo_register($mail, $userName, $password, 'http://' . Registry::get('config.http_host')), true);
    if($response['status']['code'] == 200)
    {
      $accountPlatformResponse = json_decode(fn_yotpo_create_account_platform($response['response']['app_key'], $response['response']['secret'], 'http://' . Registry::get('config.http_host')), true);        
      if($accountPlatformResponse['status']['code'] == 200)
      {
        $cSettings = Settings::instance();
        $cSettings->updateValue('yotpo_app_key', $response['response']['app_key'], 'yotpo');
        $cSettings->updateValue('yotpo_secret_token', $response['response']['secret'], 'yotpo');
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
  return Http::post(YOTPO_API_URL . '/apps/check_availability', json_encode($data), array('headers' => array('Content-Type: application/json'), 'timeout' => YOTPO_HTTP_REQUEST_TIMEOUT));
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
  return Http::post(YOTPO_API_URL . '/users.json', json_encode($data), array('headers' => array('Content-Type: application/json'), 'timeout' => YOTPO_HTTP_REQUEST_TIMEOUT));
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
      return Http::post(YOTPO_API_URL . '/apps/' . $app_key .'/account_platform', json_encode($data), array('headers' => array('Content-Type: application/json'), 'timeout' => YOTPO_HTTP_REQUEST_TIMEOUT));
    }
    return $token;
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
    $order['products'] = $orders_product_by_order_id[$order['order_id']];
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
    return "Go to the <a class='y-href' href='https://api.yotpo.com/users/b2blogin?app_key=" . $appKey ."&secret=" . $secret . "'  target='_blank'>Yotpo Admin</a> to customize the look and feel of the widget and to edit your Mail After Purchase settings.</div>";
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

  $cSettings = Settings::instance();
  if($cSettings->getValue('yotpo_is_past_order_sent', 'yotpo',Settings::ADDON_SECTION) == 'false')
  {
    return '<span class="submit-button">
          <input type="submit" name="dispatch[addons.past_orders]" value="Post past orders">
        </span>';   
  }

}

function fn_yotpo_select_language_link() {
  return 'You can find the supported language codes <a href="http://support.yotpo.com/entries/21861473-Languages-Customization-" target="_blank">here</a></br>';  
}
?>