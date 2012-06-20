<link rel="stylesheet" href="<?php echo plugins_url() . '/bebop/core/resources/css/admin.css';?>" type="text/css">

<?php include_once( 'admin_menu.php' ); ?>
<div id='bebop_admin_container'>

	<table class='bebop_table'>
		<tr class='nodata'>
			<th>Log ID</th>
			<th>Timestamp</th>
			<th>Log Type</th>
			<th>Log Message</th>
		</tr>
		<?php
		$table_row_data = bebop_tables::fetch_table_data('bp_bebop_general_log');	
		foreach( $table_row_data as $row_data ) {
			echo "<tr>
				<td>" . bebop_tables::sanitise_element($row_data->id) . "</td>" . 								//Yeah I am English :P
				"<td>" . bebop_tables::sanitise_element($row_data->timestamp) . "</td>
				<td>" . bebop_tables::sanitise_element($row_data->type) . "</td>
				<td>" . bebop_tables::sanitise_element($row_data->message) . "</td>
			</tr>";
		}
		?>
	<!-- <End bebop_table -->
	</table>
<!-- End bebop_admin_container -->
</div>
