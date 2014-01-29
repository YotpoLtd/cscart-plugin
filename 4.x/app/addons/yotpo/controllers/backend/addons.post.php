<?php
use Tygh\Settings;
use Tygh\Http;

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['addon'] == 'yotpo') 
{
    $cSettings = Settings::instance();
    $appKey = $cSettings->getValue('yotpo_app_key', 'yotpo',Settings::ADDON_SECTION);
    $secret = $cSettings->getValue('yotpo_secret_token', 'yotpo',Settings::ADDON_SECTION);
    if($mode == 'update')
    {
        if(empty($appKey) && empty($secret))
        {
            $userName = $cSettings->getValue('yotpo_user_name', 'yotpo',Settings::ADDON_SECTION);
            $password = $cSettings->getValue('yotpo_user_password', 'yotpo',Settings::ADDON_SECTION);
            $passwordConfirm = $cSettings->getValue('yotpo_user_confirm_password', 'yotpo',Settings::ADDON_SECTION);
            $mail = $cSettings->getValue('yotpo_user_email', 'yotpo',Settings::ADDON_SECTION);
            $message = fn_validate_sign_up_form($userName, $mail, $password, $passwordConfirm);
            if($message == NULL)
            {
                $signUpMessage = fn_yotpo_sign_up($userName, $mail, $password);
                if($signUpMessage == NULL)
                {
                    fn_set_notification('N', 'Yotpo sign up', 'Account successfully created', 'S');
                }
                else
                {
                    fn_set_notification('E', 'Yotpo sign up', $signUpMessage, 'S');
                }
            }
            else
            {
                fn_set_notification('E', 'Yotpo sign up', $message, 'S');
            }
        }
    }
    elseif ($mode == 'past_orders')
    {
        if (!empty($appKey) && !empty($secret))
        {
            if($cSettings->getValue('yotpo_is_past_order_sent', 'yotpo',Settings::ADDON_SECTION) == 'false')
            {
                $cSettings->updateValue('yotpo_is_past_order_sent', 'true', 'yotpo');
                    
                $data = fn_yotpo_get_past_orders($auth);
                $is_success = true;
                $message = '';
                $token = fn_grant_oauth_access($appKey, $secret);
                if(isset($token))
                {
                    foreach ($data as $post_bulk)
                    {
                        if(!is_null($post_bulk))
                        {
                            if(isset($token))
                            {
                                $post_bulk['utoken'] = $token;
                                $result = Http::post(YOTPO_API_URL . '/apps/' . $appKey . "/purchases/mass_create", json_encode($post_bulk), array('headers' => array('Content-Type: application/json'), 'timeout' => YOTPO_HTTP_REQUEST_TIMEOUT));
                                $result = json_decode($result , true);
                                if ($result['code'] != 200 && $is_success)
                                {
                                    $message = $result['message'];
                                    $is_success = false;
                                }
                            }
                        }
                    }
                }
                else 
                {
                    $is_success = false;
                    $message = 'The server could not authorize your account, check your API key, and secret';
                }
                if($is_success)
                {
                    fn_set_notification('N', 'Yotpo past orders', 'Succesfully sent past orders.', 'S');
                }
                else
                {
                    fn_set_notification('E', 'Yotpo past orders', 'The following error accourd while sending past orders: ' . $message, 'S');
                }
            }
            else 
            {
                fn_set_notification('E', 'Yotpo past orders', 'You allready post your past orders.', 'S');
            }
        }
        else 
        {
            fn_set_notification('E', 'Yotpo past orders', 'You have to set your secret and api key in order to post your past orders.', 'S');       
        }
    }
}
?>