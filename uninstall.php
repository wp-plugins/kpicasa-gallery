<?php

if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
{
	exit;
}

delete_option( 'kpicasa_gallery_config' );

?>