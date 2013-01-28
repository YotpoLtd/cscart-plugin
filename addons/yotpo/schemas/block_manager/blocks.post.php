<?php

if ( !defined('AREA') ) { die('Access denied'); }

$schema['yotpo'] = array(	
	'templates' => array(
		'addons/yotpo/blocks/widget.tpl' => array(), 
	),
	'wrappers' => 'blocks/wrappers',
);
?>