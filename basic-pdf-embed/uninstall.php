<?php 
/**
 * Uninstall plugin - Single and Multisite
 * Remove options from database upon uninstall/delete of plugin
 *
 */

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// define options name in DB
$option_name = 'bpdfe_options';
$error_log = 'bpdfe_plugin_error';

if ( !is_multisite() ) {
	// delete options if not multisite (regular WP)
    delete_option( $option_name );
    delete_option( $error_log );
} else {
	// delete options in multisite
	delete_site_option( $option_name );
	delete_site_option( $error_log );
}

?>
