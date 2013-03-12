<?php

if ( !defined('AREA') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['addon'] == 'yotpo') 
{
	$cSettings = CSettings::instance();
	$appKey = $cSettings->get_value('yotpo_app_key', 'yotpo',CSettings::ADDON_SECTION);
	$secret = $cSettings->get_value('yotpo_secret_token', 'yotpo',CSettings::ADDON_SECTION);
	if($mode == 'update')
	{
		if(empty($appKey) && empty($secret))
		{
			$userName = $cSettings->get_value('yotpo_user_name', 'yotpo',CSettings::ADDON_SECTION);
			$password = $cSettings->get_value('yotpo_user_password', 'yotpo',CSettings::ADDON_SECTION);
			$passwordConfirm = $cSettings->get_value('yotpo_user_confirm_password', 'yotpo',CSettings::ADDON_SECTION);
			$mail = $cSettings->get_value('yotpo_user_email', 'yotpo',CSettings::ADDON_SECTION);
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
			if($cSettings->get_value('yotpo_is_past_order_sent', 'yotpo',CSettings::ADDON_SECTION) == 'false')
			{
				$cSettings->update_value('yotpo_is_past_order_sent', 'true', 'yotpo');
					
				$data = fn_yotpo_get_past_orders($auth);
				$is_success = true;
				$message = '';
				foreach ($data as $post_bulk)
				{
					if(!is_null($post_bulk))
					{
						$token = fn_grant_oauth_access($appKey, $secret);
						if(isset($token))
						{
							$post_bulk['utoken'] = $token;
							list (, $result) = fn_https_request('POST', YOTPO_API_URL . '/apps/' . $appKey . "/purchases/mass_create", json_encode($post_bulk), null, null, 'application/json', null, null, null, null, null, YOTPO_HTTP_REQUEST_TIMEOUT);
							$result = json_decode($result , true);
							if ($result['code'] != 200 && $is_success)
							{
								$message = $result['message'];
								$is_success = false;
							}
						}
					}
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