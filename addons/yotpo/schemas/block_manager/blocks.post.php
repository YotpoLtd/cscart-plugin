<?php

if ( !defined('AREA') ) { die('Access denied'); }

$schema['yotpo'] = array(	
	'content' => array(
		'widget_data' => array(
			'type' => 'function',
			'function' => array('fn_get_widget_data')
		),
	),
	'templates' => array(
		'addons/yotpo/blocks/widget.tpl' => array(), 
	),
	'wrappers' => 'blocks/wrappers',
);
?>
