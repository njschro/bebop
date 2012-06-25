<?php

class bebop_extensions {
	
	function page_loader($extention){
        $config = parse_ini_file( WP_PLUGIN_DIR."/bebop/extensions/".$extention."/config.ini" );
        if( ! isset($_GET["settings"])){ 
            $page = strtolower($config['defaultpage']);
        }
        else {
            $page = strtolower($_GET["settings"]);
        }

        if( $_GET['child'] ) {
            $extention = $_GET['child'];
        }

        include WP_PLUGIN_DIR."/bebop/extensions/".$extention."/templates/admin_ ".$page.".php";
    }
	
	function get_extension_configs() {

        $config = array();
        $handle = opendir(WP_PLUGIN_DIR . "/bebop/extensions");
        
        if ( $handle ) {
            while ( false !== ( $file = readdir( $handle ) ) ) {
                if ( $file != "." && $file != ".." && $file != ".DS_Store" ) {
                    if ( file_exists( WP_PLUGIN_DIR."/bebop/extensions/" . $file . "/config.ini" ) ) {
                        $config[] = parse_ini_file( WP_PLUGIN_DIR."/bebop/extensions/" . $file . "/config.ini" );
                    }
                }
            }
        }
        return $config;
    }
	
	function extension_exist($extention) {
        if ( file_exists( WP_PLUGIN_DIR."/bebop/extensions/" . strtolower($extention) . "/core.php" ) ) {
            return true;
        }
		else {
        	return false;
		}
    }
}