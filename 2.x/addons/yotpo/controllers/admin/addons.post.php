<?php

if ( !defined('AREA') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['addon'] == 'yotpo') 
{
	$appKey = null;
	$secret = null;
	$cSettings = null;
	$config = null;
	if (method_exists('CSettings', 'instance')) 
	{
		$cSettings = CSettings::instance();
	}
	else 
	{
		$config = Registry::get('addons.yotpo');
	}
	$appKey = fn_yotpo_get_settings('yotpo_app_key',$cSettings, $config);
	$secret = fn_yotpo_get_settings('yotpo_secret_token',$cSettings, $config);	
	if($mode == 'update')
	{
		if(empty($appKey) && empty($secret))
		{
			$userName = fn_yotpo_get_settings('yotpo_user_name',$cSettings, $config);
			$password = fn_yotpo_get_settings('yotpo_user_password',$cSettings, $config);
			$passwordConfirm = fn_yotpo_get_settings('yotpo_user_confirm_password',$cSettings, $config);
			$mail = fn_yotpo_get_settings('yotpo_user_email',$cSettings, $config);
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
			$is_past_orders_sent = db_get_field("SELECT is_sent_past_order FROM ?:addon_yotpo");
			if(is_null($is_past_orders_sent))
			{
				db_query('INSERT INTO ?:addon_yotpo ?e',array('is_sent_past_order' => 1));
					
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
								$result = fn_yotpo_make_post_request($post_bulk, YOTPO_API_URL . '/apps/' . $appKey . '/purchases/mass_create');
								if ($result['status_code'] != 200 && $is_success)
								{
									$message = $result['status_message'];
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