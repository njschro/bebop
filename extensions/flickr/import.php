<?php
/**
 * Extension Import function. You will need to modify this function slightly ensure all values are added to the database.
 * Please see the section below on how to do this.
 */

//replace 'flickr' with the 'name' of your extension, as defined in your config.php file.
function bebop_flickr_import( $extension ) {
	global $wpdb, $bp;
	if ( empty( $extension ) ) {
		bebop_tables::log_general( 'Importer', 'The $extension parameter is empty.' );
		return false;
	}
	else if ( ! bebop_tables::check_option_exists( 'bebop_' . $extension . '_consumer_key' ) ) {
		bebop_tables::log_error( 'Importer', 'No consumer key was found for ' . $extension );
		return false;
	}
	else {
		$this_extension = bebop_extensions::get_extension_config_by_name( $extension );
	}
	
	//item counter for in the logs
	$itemCounter = 0;
	$user_metas = bebop_tables::get_user_ids_from_meta_type( $this_extension['name'] );
	if ( $user_metas ) {
		foreach ( $user_metas as $user_meta ) {
			//Ensure the user is currently wanting to import items.
			if ( bebop_tables::get_user_meta_value( $user_meta->user_id, 'bebop_' . $this_extension['name'] . '_active_for_user' ) == 1 ) {
				
				$user_feeds = bebop_tables::get_user_feeds( $user_meta->user_id , $this_extension['name']);
				foreach ($user_feeds as $user_feed ) {
					$errors = null;
					$items 	= null;
					
					$username = $user_feed->meta_value;
					
					$import_username = str_replace( ' ', '_', $username );
					//Check the user has not gone past their import limit for the day.
					if ( ! bebop_filters::import_limit_reached( $this_extension['name'], $user_meta->user_id, $import_username ) ) {
						
						/* 
						 * ******************************************************************************************************************
						 * Depending on the data source, you will need to switch how the data is retrieved. If the feed is RSS, use the 	*
						 * SimplePie method, as shown in the youtube extension. If the feed is oAuth API based, use the oAuth implementation*
						 * as shown in thr twitter extension. If the feed is an API without oAuth authentication, use SlideShare			*
						 * ******************************************************************************************************************
						 */
						 
						//We are not using oauth for flickr - so just build the api request and send it using our bebop-data class.
						//If you are using a service that uses oAuth, then use the oAuth class and set the paramaters required for the request.
						//These are custom for flickr - edit these to match the paremeters required by the API.
						
						$data_request = new bebop_data();
						
						//send a request to see if we have a username or a user_id.
						$data_request->set_parameters( 
									array( 
												'method'		=> 'flickr.people.getPublicPhotos',
												'api_key' 		=> bebop_tables::get_option_value( 'bebop_' . $this_extension['name'] . '_consumer_key' ),
												'user_id'		=> $username,
												'extras'		=> 'date_upload,url_m,url_t,description',
									)
						);
						$query = $data_request->build_query( $this_extension['data_feed'] );
						$data = $data_request->execute_request( $query );
						$data = simplexml_load_string( $data );
						
						//if the previous request failed. we have a username not a user_id.
						if ( empty ( $data->photos ) ) {
						
							//Go and get the user_id
							$data_request->set_parameters( 
										array( 
													'method'		=> 'flickr.urls.lookupuser',
													'api_key' 		=> bebop_tables::get_option_value( 'bebop_' . $this_extension['name'] . '_consumer_key' ),
													'url' 			=> 'http://www.flickr.com/photos/' . $username,
										)
							);
							$query = $data_request->build_query( $this_extension['data_feed'] );
							$data = $data_request->execute_request( $query );
							$data = simplexml_load_string( $data );
							
							//retry the request
							$data_request->set_parameters( 
										array( 
													'method'		=> 'flickr.people.getPublicPhotos',
													'api_key' 		=> bebop_tables::get_option_value( 'bebop_' . $this_extension['name'] . '_consumer_key' ),
													'user_id'		=> urldecode($data->user['id']),
													'extras'		=> 'date_upload,url_m,url_t,description',
										)
							);
							$query = $data_request->build_query( $this_extension['data_feed'] );
							$data = $data_request->execute_request( $query );
							$data = simplexml_load_string( $data );
						}
						
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
						 * 		$items					- Must point to the items that will be imported into the plugin.
						 * 		$id						- Must be the ID of the item returned through the data feed.
						 * 		$description			- The actual content of the imported item.
						 * 		$item_published			- The time the item was published.
						 * 		$action_link			- This is where the link will point to - i.e. where the user can click to get more info.
						 */
						
						
						//Edit the following variable to point to where the relevant content is being stored in the :
						$items 	= $data->photos->photo;
						
						foreach ( $items as $item ) {
							if ( ! bebop_filters::import_limit_reached( $this_extension['name'], $user_meta->user_id, $import_username ) ) {
								//Edit the following variables to point to where the relevant content is being stored:
								$id					= $item['id'];
								$action_link		= $this_extension['action_link'] . $item['owner'] . '/' . $id;
								$description		= $item['description'];
								$item_published		= gmdate( 'Y-m-d H:i:s' , (INT)$item['dateupload']);
								//Stop editing - you should be all done.
								
								
								//generate an $item_id
								$item_id = bebop_generate_secondary_id( $user_meta->user_id, $id, $item_published );
								
								//check if the secondary_id already exists
								$secondary = bebop_tables::fetch_individual_oer_data( $item_id );
								//if the id is found, we have the item in the database and all following items (feeds return most recent items first). Move onto the next user..
								if ( ! empty( $secondary->secondary_item_id ) ) {
									break;
								}
								
								//Only for content which has a description.
								if( ! empty( $description ) ) {
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
													'username'			=> $import_username,							//required for day counter increases.
													'content'			=> $item_content,
													'content_oembed'	=> $this_extension['content_oembed'],
													'item_id'			=> $item_id,
													'raw_date'			=> $item_published,
													'actionlink'		=> $action_link . '/lightbox',
												)
								) ) {
									$itemCounter++;
								}
							}
						}
					}//End if ( ! bebop_filters::import_limit_reached( $this_extension['name'], $user_meta->user_id, $import_username ) ) {
				}//End foreach ($user_feeds as $user_feed ) {
			}
		}
	}
	//return the result
	return $itemCounter . ' ' . $this_extension['content_type'] . 's';
}