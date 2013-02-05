<?php

if ( !defined('AREA') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'update' && $_REQUEST['addon'] == 'yotpo') 
{
	$cSettings = CSettings::instance();
	$appKey = $cSettings->get_value('yotpo_app_key', 'yotpo',CSettings::ADDON_SECTION);
	$secret = $cSettings->get_value('yotpo_secret_token', 'yotpo',CSettings::ADDON_SECTION);
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
?>