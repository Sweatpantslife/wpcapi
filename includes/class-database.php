<?php
namespace ElementorMetaCAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database table creation and modifications.
 */
class Database {

	/**
	 * Creates or updates the custom tables for the plugin.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'emcapi_logs';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id varchar(100) NOT NULL,
			event_name varchar(100) NOT NULL,
			payload longtext NOT NULL,
			status varchar(50) NOT NULL,
			api_response longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY event_id (event_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		ob_start();
		dbDelta( $sql );
		ob_end_clean();
	}
	
	/**
	 * Insert a log record.
	 * 
	 * @param array $data Data to insert.
	 * @return int|false The inserted row ID, or false on failure.
	 */
	public static function insert_log( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'emcapi_logs';
		
		$wpdb->insert(
			$table_name,
			$data,
			[
				'%s', // event_id
				'%s', // event_name
				'%s', // payload
				'%s', // status
				'%s', // api_response
			]
		);
		
		return $wpdb->insert_id;
	}

	/**
	 * Update a log record.
	 * 
	 * @param int $id The log ID.
	 * @param array $data Data to update.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function update_log( $id, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'emcapi_logs';
		
		// Map values to strings, assuming all updates here are string strings.
		$format = array_fill( 0, count( $data ), '%s' );

		return $wpdb->update(
			$table_name,
			$data,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);
	}
	
	/**
	 * Get a log record by ID.
	 */
	public static function get_log( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'emcapi_logs';
		
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
	}
}
