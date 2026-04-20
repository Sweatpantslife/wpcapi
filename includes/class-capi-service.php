<?php
namespace ElementorMetaCAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service to handle Meta CAPI payload preparation and sending.
 */
class CAPI_Service {

	/**
	 * Process form submission.
	 *
	 * @param string $event_name The CAPI event name.
	 * @param array  $mapped_data User data mapped from form.
	 * @param mixed  $record Optional form record object for extra context.
	 */
	public static function process_submission( $event_name, $mapped_data, $record = null ) {
		// Generate standard CAPI unique Event ID.
		$event_id = wp_generate_uuid4();

		$user_data = self::prepare_user_data( $mapped_data );

		$custom_data = [];
		if ( $record ) {
            $form_name = $record->get_form_settings('form_name');
            if ($form_name) {
                $custom_data['form_name'] = $form_name;
            }
		}

		$payload = [
			'data' => [
				[
					'event_name' => $event_name,
					'event_time' => time(),
					'event_id'   => $event_id,
					'event_source_url' => self::get_event_source_url(),
					'action_source' => 'website',
					'user_data' => $user_data,
					'custom_data' => $custom_data,
				]
			]
		];

		// Build a log-safe copy of the payload: strip tracking cookie values
		// (_fbp / _fbc) so they are not persisted in the database.
		$log_payload = $payload;
		unset( $log_payload['data'][0]['user_data']['fbp'] );
		unset( $log_payload['data'][0]['user_data']['fbc'] );

		// Log into DB as pending.
		$log_id = Database::insert_log([
			'event_id'   => $event_id,
			'event_name' => $event_name,
			'payload'    => wp_json_encode( $log_payload ),
			'status'     => 'pending',
		]);

		if ( $log_id ) {
			self::send_payload( $payload, $log_id );
		}
	}

	/**
	 * Prepare and hash user data.
	 *
	 * @param array $data Raw user data.
	 * @return array Hashed and formatted user data.
	 */
	private static function prepare_user_data( $data ) {
		$user_data = [
			'client_user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
			'client_ip_address' => self::get_client_ip(),
		];

		// Capture fbp and fbc cookies.
		if ( isset( $_COOKIE['_fbp'] ) ) {
			$user_data['fbp'] = sanitize_text_field( $_COOKIE['_fbp'] );
		}
		if ( isset( $_COOKIE['_fbc'] ) ) {
			$user_data['fbc'] = sanitize_text_field( $_COOKIE['_fbc'] );
		}

		// Hash specific fields
		$hash_fields = [ 'em', 'ph', 'fn', 'ln', 'ct', 'zp' ];
		foreach ( $hash_fields as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				$val = $data[ $field ];
				
				// Normalization rules per Meta: lowercase, trim.
				$val = strtolower( trim( (string) $val ) );
				
				// Special phone normalization (strip non numeric except +)
				if ( 'ph' === $field ) {
					$val = preg_replace( '/[^0-9+]+/', '', $val );
				}
				
				$user_data[ $field ] = hash( 'sha256', $val );
			}
		}

		return array_filter( $user_data ); // Remove empty hashes
	}

	/**
	 * Send payload to Meta CAPI.
	 *
	 * @param array $payload The event payload.
	 * @param int   $log_id  The DB log record ID.
	 */
	public static function send_payload( $payload, $log_id ) {
		$pixel_id = get_option( 'emcapi_pixel_id' );
		$token    = get_option( 'emcapi_access_token' );

		if ( empty( $pixel_id ) || empty( $token ) ) {
			Database::update_log( $log_id, [
				'status'       => 'failed',
				'api_response' => 'Missing Pixel ID or Access Token.',
			]);
			return;
		}

		$url = "https://graph.facebook.com/v19.0/" . rawurlencode( $pixel_id ) . "/events";

		// Add the access token to the POST body, not the URL, so it is not
		// recorded in web-server or proxy access logs.
		$send_payload                 = $payload;
		$send_payload['access_token'] = $token;

		// We execute this synchronously. It adds minimal delay but guarantees
		// we log the success/failure state to DB definitively.
		$response = wp_remote_post( $url, [
			'body'    => wp_json_encode( $send_payload ),
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'timeout' => 10,
		]);

		if ( is_wp_error( $response ) ) {
			Database::update_log( $log_id, [
				'status'       => 'failed',
				'api_response' => $response->get_error_message(),
			]);
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 === $code ) {
			Database::update_log( $log_id, [
				'status'       => 'success',
				'api_response' => $body,
			]);
		} else {
			Database::update_log( $log_id, [
				'status'       => 'failed',
				'api_response' => "HTTP {$code}: " . $body,
			]);
		}
	}

	/**
	 * Helper to get the actual client IP.
	 *
	 * Only REMOTE_ADDR is used because HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR
	 * are HTTP headers that any client can forge freely.
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '';
	}

	/**
	 * Return a validated event_source_url for the CAPI payload.
	 *
	 * The HTTP Referer header is used only when it points to a URL within
	 * this site, preventing visitors from injecting arbitrary external URLs
	 * into Meta event data. Falls back to the home URL otherwise.
	 *
	 * @return string
	 */
	private static function get_event_source_url() {
		$referer = wp_get_referer();
		if ( $referer && wp_parse_url( $referer, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return sanitize_url( $referer );
		}
		return home_url();
	}
}
