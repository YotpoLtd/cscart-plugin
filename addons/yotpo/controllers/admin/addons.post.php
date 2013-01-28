<?php

if ( !defined('AREA') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'update' && $_REQUEST['addon'] == 'yotpo') 
{
	$yotpoSettings = CSettings::instance()->get_section_by_name('yotpo', CSettings::ADDON_SECTION);
	$allSettings = CSettings::instance()->get_list($yotpoSettings['section_id']);
	$settings = $allSettings['settings'];
	$appKey = NULL;
	$secret = NULL;

	$appKeyObjectId = NULL;
	$secretKeyObjectId = NULL;
	foreach($settings as $key=>$value)
	{
		if($value['name'] == 'yotpo_app_key')
		{
			$appKey = $value['value'];
			$appKeyObjectId = $value['object_id'];
		}
		elseif($value['name'] == 'yotpo_secret_token')
		{
			$secret = $value['value'];
			$secretKeyObjectId = $value['object_id'];
		}	
	}

	if(empty($appKey) && empty($secret))
	{
		$userName = NULL;
		$password = NULL;
		$passwordConfirm = NULL;
		$mail = NULL;
		$signUp = $allSettings['signup'];
		foreach($signUp as $key=>$value)
		{
			if($value['name'] == 'yotpo_user_email')
			{
				$mail = $value['value'];
			}
			elseif($value['name'] == 'yotpo_user_name')
			{
				$userName = $value['value'];
			}
			elseif ($value['name'] == 'yotpo_user_password') 
			{
				$password = $value['value'];	
			}
			elseif ($value['name'] == 'yotpo_user_confirm_password') 
			{
				$passwordConfirm = $value['value'];	
			}				
		}
		$message = fn_validate_sign_up_form($userName, $mail, $password, $passwordConfirm);
		if($message == NULL)
		{
			$signUpMessage = fn_yotpo_sign_up($userName, $mail, $password, $appKeyObjectId, $secretKeyObjectId);
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