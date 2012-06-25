<?php
//File used to load pages

function bebop_admin_menu() {

    if ( ! is_super_admin() ) {
	    return false;
    }
    add_menu_page(
    	__('Bebop Admin', 'bebop'), 
    	__('Bebop', 'bebop'), 
    	'manage_options',
    	'bebop_admin', 
    	'bebop_admin_pages',
    	WP_PLUGIN_URL . "/bebop/core/resources/images/bebop_icon.png"
     );
     add_submenu_page( 'bebop_admin', 'Admin Main', 'Admin Main', 'manage_options', 'bebop_admin', 'bebop_admin_pages' );
     add_submenu_page( 'bebop_admin', 'General Settings', 'General Settings', 'manage_options', 'bebop_settings', 'bebop_admin_pages' );
	 add_submenu_page( 'bebop_admin', 'OER Providers', 'OER Providers', 'manage_options', 'bebop_oer_providers', 'bebop_admin_pages' );
	 add_submenu_page( 'bebop_admin', 'Cron', 'Cron', 'manage_options', 'bebop_cron', 'bebop_admin_pages' );
	 add_submenu_page( 'bebop_admin', 'Error Log', 'Error Log', 'manage_options', 'bebop_error_log', 'bebop_admin_pages' );
	 add_submenu_page( 'bebop_admin', 'General Log', 'General Log', 'manage_options', 'bebop_general_log', 'bebop_admin_pages' );
	 
	 //provider menu
	 foreach( bebop_extensions::get_extension_configs() as $extension ) {
        if ( bebop_tables::get_option( "bebop_" . $extension['name'] . "_provider" ) ) {
            if( ! $extension['parent'] ) {
            	add_submenu_page('bebop_admin', ucfirst($extension['displayname']), ucfirst($extension['displayname']), 'manage_options', 'bebop_'.$extension['name'], 'bebop_'.$extension['name']);
            }
        }
    }
}









function bebop_admin_pages() {
	if ( $_GET["page"] == "bebop_admin" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_admin.php";
	}
	/*else if ( $_GET["page"] == "bebop_settings" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_settings.php";
	}*/
	else if ( $_GET["page"] == "bebop_oer_providers" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_oer_providers.php";
	}
	/*else if ( $_GET["page"] == "bebop_cron" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_cron.php";
	}*/
	else if ( $_GET["page"] == "bebop_error_log" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_error_log.php";
	}
	else if ( $_GET["page"] == "bebop_general_log" ){
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_general_log.php";
	}
	else {
		echo '<div class="bebop_error_box"><b>Bebop Error:</b> action not found. Loaded home instead.</div>';
		include WP_PLUGIN_DIR . "/bebop/core/templates/bebop_admin.php";
	}
}  
//add_action('admin_menu', 'bebop_admin_menu');
add_action('network_admin_menu', 'bebop_admin_menu');
?> 