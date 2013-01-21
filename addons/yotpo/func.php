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

  $curl = fn_check_curl();

  if (
      Registry::is_exist('addons.yotpo.yotpo_mail_after_purchase') && 
      Registry::get('addons.yotpo.yotpo_mail_after_purchase') == true && 
      Registry::is_exist('addons.yotpo.yotpo_app_key') && 
      Registry::get('addons.yotpo.yotpo_app_key') != '' && 
      Registry::is_exist('addons.yotpo.yotpo_secret_token') && 
      Registry::get('addons.yotpo.yotpo_secret_token') != '' && 
      $curl
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
    $data['utoken'] = $token;
    $customer = NULL;
    file_put_contents("testFile.txt", "order_info == \n".json_encode($order_info), FILE_APPEND);
//     //     $order = new Order((int)$params['id_order']);
//     //     $customer = new Customer((int)$order->id_customer);
//     //   $data["order_date"] = $order->date_add;
      $data["email"] = $order_info['$email'];
      $data["customer_name"] = $order_info['firstname'] . ' ' . $order_info['lastname'];
      $data["order_id"] = $order_info['order_id'];



      $data['platform'] = 'prestashop';
      file_put_contents("testFile.txt", "data == \n".json_encode($data), FILE_APPEND);


      $products = $order_info['items'];
      $products_arr = array();

//     //     $currency = $context->getCurrency($params['id_order']);
//     //   $data["currency_iso"] = $currency['iso_code'];

      foreach ($products as $product) {

        $product_data = array();

    //     $full_product = new Product((int)($product['product_id']), false, (int)($params['cookie']->id_lang));      
    //     $product_data['url'] = $full_product->getLink();  
    //     $product_data['name'] = $full_product->name;
    //     $product_data['image'] = $context->_getProductImageUrl($product['product_id']);
    //     $product_data['description'] = strip_tags($full_product->description);

    //     if (isset($product['total_price_tax_excl']))
    //     $product_data['price'] = $product['total_price_tax_excl'];
    //     else
    //   $product_data['price'] = $product['product_price'];

    //     $products_arr[$product['product_id']] = $product_data;
    //   }

    //   $data['products'] = $products_arr;
    //   $this->makePostRequest(self::YOTPO_API_URL . '/apps/' . $app_key . "/purchases/", $data);
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
    file_put_contents("testFile.txt", json_encode($e), FILE_APPEND);
    return NULL;
  }
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