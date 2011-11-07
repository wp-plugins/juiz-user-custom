<?php
/*
	The uninstall file
	(to make your blog always clean when you need to test plugins :p)
*/

global $wpdb;

if( !defined( 'ABSPATH') &&  !defined('WP_UNINSTALL_PLUGIN') )
	    exit();
	
	// $juiz_user_infos = get_user_meta( 1, 'juiz_user_custom_fields', true );
	$juiz_user_infos = get_option( 'juiz_user_custom_fields' );
	$juiz_user_infos_a = unserialize ( $juiz_user_infos );
	
	foreach($juiz_user_infos_a as $k => $v) {
		
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key = %s ", 'juiz_'.$k));
		
	}
	
	// $delete_plugin_settings = delete_user_meta ( 1, 'juiz_user_custom_fields' );
	delete_option( 'juiz_user_custom_fields', $juiz_user_infos );

?>