<?php

/**
 * Ajax Handler for generating a reports csv file.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Ajax;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\CSV\Report_CSV_Generator;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * Generate CSV Ajax Handler
 */
class Generate_CSV_Ajax {

	public const ACTION    = 'wlf_generate_csv';
	public const NONCE_KEY = 'wlf_generate_csv_nonce';

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Access to the CSV Generator.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_CSV_Generator
	 */
	private Report_CSV_Generator $csv_generator;

	/**
	 * Create instance of Generate_CSV_Ajax.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->report_repository = new Report_Repository();
		$this->csv_generator     = new Report_CSV_Generator();
	}

	/**
	 * Invoke the ajax handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __invoke(): void {

		// Check we have a the nonce and its valid.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], self::NONCE_KEY ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}

		// Check we have a report id.
		if ( ! isset( $_POST['report'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			wp_send_json_error(
				array(
					'message' => __( 'Invalid report id.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}

		// Get the report.
		$report = $this->report_repository->find_by_report_id( \sanitize_text_field( $_POST['report'] ) ); //phpcs:ignore WordPress.Security.NonceVerification

		// If we dont have a report, return an error.
		if ( ! $report ) {
			wp_send_json_error(
				array(
					'message' => __( 'Report not found.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}
		// Generate the CSV.
		$csv = $this->csv_generator->generate( $report );

		// If we dont have a valid CSV path, return an error.
		if ( ! $csv || ! \file_exists( $csv ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'CSV could not be generated.', 'wpcomsp_wayback_link_fixer' ),
				)
			);
		}

		// Get the assumed URL.
		$url = Report_Helper::get_report_csv_url( $report );

		// Send the response.
		wp_send_json_success(
			array(
				'url'  => $url,
				'path' => $csv,
			)
		);
	}
}
