<h3>
<?php if( ! isset($_GET['oer']) ) {
    echo "<h3>Provider</h3>";
    echo "Provider Info";
}
?>
</h3>

<div class="buddystream_album_navigation_links">
    <ul>
        <?php

        //get the active extension
        foreach( bebop_extensions::get_extension_configs() as $extension ) {
            if(bebop_tables::get_option('bebop_'.$extension['name'].'_provider') == "on") {
                echo '<li><a href="?oer=' . $extension['name'] . '">'.ucfirst($extension['displayname']).'</a></li>';         
                $activeExtensions[] = $extension['name'];
            }
        }
		if( count($activeExtensions) == 0 ) {
			echo "No extensions are currently active. Please activate them in the bebop OER provides admin panel.";
		}
        ?>
    </ul>
</div>
<br/><br/>

<?php
if( isset($_GET['oer']) ) {
    include(WP_PLUGIN_DIR."/bebop/extensions/".$_GET['oer']."/templates/user_settings.php");
}
?>