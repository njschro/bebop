<?php
/**
 * Extension Import function. You will need to modify this function slightly ensure all values are added to the database.
 * Please see the section below on how to do this.
 */

//replace 'slideshare' with the 'name' of your extension, as defined in your config.php file.
function bebop_vimeo_import( $extension ) {
	global $wpdb, $bp;
	if ( empty( $extension ) ) {
		bebop_tables::log_general( 'Importer', 'The $extension parameter is empty.' );
		return false;
	}
	else {
		$this_extension = bebop_extensions::get_extension_config_by_name( $extension );
	}
	
	//item counter for in the logs
	$itemCounter = 0;
	$user_metas = bebop_tables::get_user_ids_from_meta_name( 'bebop_' . $this_extension['name'] . '_username' );
	if ( $user_metas ) {
		foreach ( $user_metas as $user_meta ) {
			$errors = null;
			$items 	= null;
			
			//Ensure the user is currently wanting to import items.
			if ( bebop_tables::get_user_meta_value( $user_meta->user_id, 'bebop_' . $this_extension['name'] . '_active_for_user' ) == 1 ) {
				//Check the user has not gone past their import limot for the day.
				if ( ! bebop_filters::import_limit_reached( $this_extension['name'], $user_meta->user_id ) ) {
					
					/* 
					 * ******************************************************************************************************************
					 * Depending on the data source, you will need to switch how the data is retrieved. If the feed is RSS, use the 	*
					 * SimplePie method, as shown in the youtube extension. If the feed is oAuth API based, use the oAuth implementation*
					 * as shown in thr twitter extension. If the feed is an API without oAuth authentication, use SlideShare			*
					 * ******************************************************************************************************************
					 */
					 
					//We are not using oauth for slideshare - so just build the api request and send it using our bebop-data class.
					//If you are using a service that uses oAuth, then use the oAuth class and set the paramaters required for the request.
					//These are custom for slideshare - edit these to match the paremeters required by the API.
					
					$data_request = new bebop_data();
					
					$query = $this_extension['data_feed'] . bebop_tables::get_user_meta_value( $user_meta->user_id, 'bebop_' . $this_extension['name'] . '_username' ) . '/' . 'videos.php';
					$data = $data_request->execute_request( $query );
					$data = unserialize( $data );
					
					/* 
					 * ******************************************************************************************************************
					 * We can get as far as loading the items, but you will need to adjust the values of the variables below to match 	*
					 * the values from the extension's feed.																			*
					 * This is because each feed return data under different parameter names, and the simplest way to get around this is*
					 * to quickly match the values. To find out what values you should be using, consult the provider's documentation.	*
					 * You can also contact us if you get stuck - details are in the 'support' section of the admin homepage.			*
					 * ******************************************************************************************************************
					 * 
					 * Values you will need to check and update are:
					 * 		$errors 				- Must point to the error value
					 * 		$items					- Must point to the items that will be imported into the plugin.
					 * 		$item_id				- Must be the ID of the item returned through the data feed.
					 * 		$item_content			- The actual content of the imported item.
					 * 		$item_published			- The time the item was published.
					 * 		$action_link			- This is where the link will point to - i.e. where the user can click to get more info.
					 */
					
						
					//Edit the following variable to point to where the relevant content is being stored in the :
					$items 	= $data;
					
					foreach ( $items as $item ) {
						//vimeo returns the item as an array, so cast it to an object for simplicity.
						$item = (object)$item; //
						
						if ( ! bebop_filters::import_limit_reached( $this_extension['name'], $user_meta->user_id ) ) {
							
							//Edit the following variables to point to where the relevant content is being stored:
							$item_id			= $item->id;
							$action_link		= $item->url;
							$description		= $item->description;
							$item_published 	= gmdate( 'Y-m-d H:i:s', strtotime( $item->upload_date ) );
							//Stop editing - you should be all done.
							
							//check if the secondary_id already exists
							$secondary = bebop_tables::fetch_individual_oer_data( $user_meta->user_id .'_' . $item_id );
							//if the id is found, we have the item in the database and all following items (feeds return most recent items first). Move onto the next user..
							if ( ! empty( $secondary->secondary_item_id ) ) {
								break;
							}
							
							//Only for content which has a description.
							if( ! empty( $description) ) {
								//crops the content if it is too long
								if ( strlen( $description ) > 500 ) {
									$description = substr( $description, 0, 500 ) . " <a href='" . $action_link . "'>read more</a>";
								}
								
								//This manually puts the link and description together with a line break, which is needed for oembed.
								$item_content = $action_link . '
								' . $description;
							}
							else {
								$item_content = $action_link;
							}
							
							
							if ( bebop_create_buffer_item(
											array(
												'user_id'			=> $user_meta->user_id,
												'extention'			=> $this_extension['name'],
												'type'				=> $this_extension['content_type'],
												'content'			=> $item_content,
												'content_oembed'	=> $this_extension['content_oembed'],
												'item_id'			=> $item_id,
												'raw_date'			=> $item_published,
												'actionlink'		=> $action_link,
											)
							) ) {
								$itemCounter++;
							}
						}
					}
				}
			}
		}
	}
	//return the result
	return $itemCounter . ' ' . $this_extension['content_type'] . 's';
}