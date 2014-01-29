<?php

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

$schema['yotpo'] = array(   
    'templates' => array(
        'addons/yotpo/blocks/widget.tpl' => array(), 
    ),
    'wrappers' => 'blocks/wrappers',
);

return $schema;
?>