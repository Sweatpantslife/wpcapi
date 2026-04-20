<?php
namespace ElementorMetaCAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Elementor\Controls_Manager;
use \ElementorPro\Modules\Forms\Classes\Action_Base;

/**
 * Custom Elementor Form Action for Meta CAPI.
 */
class Elementor_Action extends Action_Base {

	/**
	 * Get action Name.
	 */
	public function get_name() {
		return 'meta_capi';
	}

	/**
	 * Get action Label.
	 */
	public function get_label() {
		return __( 'Meta CAPI Event', 'elementor-meta-capi' );
	}

	/**
	 * Register Settings Section in Elementor Form editor.
	 *
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_meta_capi',
			[
				'label' => __( 'Meta CAPI Settings', 'elementor-meta-capi' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'emcapi_event_name',
			[
				'label'   => __( 'Event Name', 'elementor-meta-capi' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'Lead',
				'options' => [
					'Lead'             => 'Lead',
					'Contact'          => 'Contact',
					'Purchase'         => 'Purchase',
					'Schedule'         => 'Schedule',
					'SubmitApplication'=> 'Submit Application',
				],
			]
		);

		// Map to standard user data fields.
		$widget->add_control(
			'emcapi_fields_map',
			[
				'label' => __( 'Field Mapping', 'elementor-meta-capi' ),
				'type' => \ElementorPro\Modules\Forms\Controls\Fields_Map::CONTROL_TYPE,
				'default' => [
					[ 'remote_id' => 'em', 'remote_label' => __( 'Email', 'elementor-meta-capi' ) ],
					[ 'remote_id' => 'ph', 'remote_label' => __( 'Phone', 'elementor-meta-capi' ) ],
					[ 'remote_id' => 'fn', 'remote_label' => __( 'First Name', 'elementor-meta-capi' ) ],
					[ 'remote_id' => 'ln', 'remote_label' => __( 'Last Name', 'elementor-meta-capi' ) ],
					[ 'remote_id' => 'ct', 'remote_label' => __( 'City', 'elementor-meta-capi' ) ],
					[ 'remote_id' => 'zp', 'remote_label' => __( 'Zip Code', 'elementor-meta-capi' ) ],
				],
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->end_controls_section();
	}

	/**
	 * Run the action upon Form Submission.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );
		$event_name = ! empty( $settings['emcapi_event_name'] ) ? $settings['emcapi_event_name'] : 'Lead';
		
		// Map the remote fields from Elementor's fields map.
		$raw_fields = $record->get( 'fields' );
		$mapped_data = [];
		
		if ( ! empty( $settings['emcapi_fields_map'] ) && is_array( $settings['emcapi_fields_map'] ) ) {
			foreach ( $settings['emcapi_fields_map'] as $map_item ) {
				if ( empty( $map_item['remote_id'] ) || empty( $map_item['local_id'] ) ) {
					continue;
				}
				
				$local_id = $map_item['local_id'];
				$remote_id = $map_item['remote_id'];
				
				if ( isset( $raw_fields[ $local_id ] ) ) {
					$mapped_data[ $remote_id ] = $raw_fields[ $local_id ]['value'];
				}
			}
		}

		// Use CAPI_Service to process and send the event.
		if ( class_exists( '\ElementorMetaCAPI\CAPI_Service' ) ) {
			\ElementorMetaCAPI\CAPI_Service::process_submission( $event_name, $mapped_data, $record );
		}
	}

	/**
	 * On Export - remove sensitive data if any.
	 */
	public function on_export( $element ) {
		return $element;
	}
}
