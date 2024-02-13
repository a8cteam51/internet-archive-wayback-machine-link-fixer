<?php

/**
 * Write CSV file from array
 *
 * @since 1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\CSV;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Writer
 */
class CSV_Writer {

	/**
	 * The delimiter to use for the CSV file.
	 *
	 * @var string
	 */
	private string $delimiter = ',';

	/**
	 * The base path for the CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $base_path;

	/**
	 * The CSV Headers
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>|null
	 */
	private ?array $headers = null;

	/**
	 * The Filename to create
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $filename = 'report.csv';

	/**
	 * Write buffer.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private array $buffer = array();

	/**
	 * Access to the WP Filesystem API.
	 *
	 * @since 1.0.0
	 *
	 * @var \WP_Filesystem_Base
	 */
	private ?\WP_Filesystem_Base $filesystem = null;

	/**
	 * Create an instance of the CSV Generator.
	 *
	 * @param string      $base_path The base path for the CSV file.
	 * @param string|null $delimiter The delimiter to use for the CSV file.
	 */
	public function __construct( string $base_path, ?string $delimiter = null ) {

		// Set the base path and ensure it has a trailing slash.
		$this->base_path = \trailingslashit( \untrailingslashit( $base_path ) );

		// Set the delimiter.
		if ( null !== $delimiter ) {
			$this->delimiter = $delimiter;
		}

		// Initialize the file system.
		$this->filesystem = $this->get_filesystem();
	}

	/**
	 * Set the file system , if it was not set in the constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_Filesystem_Base
	 */
	public function get_filesystem(): \WP_Filesystem_Base {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}


	/**
	 * Set the headers for the CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $headers The headers to use.
	 *
	 * @return void
	 */
	public function set_headers( array $headers ): void {
		$this->headers = array_map( 'esc_attr', $headers );
	}

	/**
	 * Set the filename for the CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename The filename to use.
	 *
	 * @return void
	 */
	public function set_filename( string $filename ): void {
		// If file name does not end in .csv, add it.
		if ( '.csv' !== substr( $filename, -4 ) ) {
			$filename .= '.csv';
		}

		$this->filename = $filename;
	}

	/**
	 * Generate the CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data The data to generate the CSV file from.
	 *
	 * @return string The path to the CSV file.
	 */
	public function generate( array $data ): string {
		// Reset the filesystem if its not already (Bug with CLI)
		$this->filesystem = $this->get_filesystem();

		// Get the headers from the data, if not set.
		$headers = $this->headers ?? $this->get_headers_from_data( $data );

		// Normalize the data.
		$data = $this->normalize_data( $data );

		// Reset the buffer.
		$this->reset_buffer();

		// Add header to the bugger.
		$this->add_row_to_buffer( $headers );

		// Iterate through the data and add the rows to the buffer.
		foreach ( $data as $row ) {
			$this->add_row_to_buffer( $row );
		}

		// Write the buffer to the file.
		$this->filesystem->put_contents( $this->base_path . $this->filename, join( "\n", $this->buffer ), 0644 );

		// Return the path to the CSV file.
		return $this->base_path . $this->filename;
	}

	/**
	 * Reset the buffer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function reset_buffer(): void {
		$this->buffer = array();
	}

	/**
	 * Adds a row to the write buffer.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $row The row to add to the buffer.
	 *
	 * @return void
	 */
	private function add_row_to_buffer( array $row ): void {
		$this->buffer[] = sprintf( '%s', join( $this->delimiter, $row ) );
	}

	/**
	 * Get the assumed headers from data passed.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data The data to get the headers from.
	 *
	 * @return array<string>
	 */
	private function get_headers_from_data( array $data ): array {
		// Get the first row of data.
		$first_row = reset( $data );

		// If the first row is not an array, return an empty array.
		if ( ! is_array( $first_row ) ) {
			return array();
		}

		// Get the keys from the first row.
		$headers = array_keys( $first_row );

		// Return the headers.
		return $headers;
	}

	/**
	 * Normalize the data to be used in the CSV file.
	 *
	 * Turn all Rows into arrays.
	 * Ensure all have same column count.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data The data to normalize.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws \Exception If the data is not an array.
	 */
	private function normalize_data( array $data ): array {

		// If we have an empty array, return it.
		if ( empty( $data ) ) {
			return $data;
		}

		// Cast all rows to array if not already an array.
		$data = array_map( fn( $row ): array => is_array( $row ) ? $row : array( $row ), $data );

		// Get max number of columns.
		$max_columns = max( array_map( 'count', $data ) );

		// Ensure all data is the same length and all columns are cast to string.
		return array_map(
			function ( array $row ) use ( $max_columns ): array {
				// If the row is not the same length as the max columns, pad it.
				if ( count( $row ) !== $max_columns ) {
					$row = array_pad( $row, $max_columns, '' );
				}

				// Cast columns.
				return array_map( array( $this, 'stringify_value' ), $row );
			},
			$data
		);
	}

	/**
	 * Stringify a value for CSV.
	 *
	 * @param mixed $value The value to stringify.
	 *
	 * @return string The stringified value.
	 */
	private function stringify_value( $value ): string {
		// If a int or float.
		if ( is_int( $value ) || is_float( $value ) ) {
			$value = (string) $value;
		}

						// If a bool, set as TRUE|FALSE.
		if ( is_bool( $value ) ) {
			$value = true === $value ? 'TRUE' : 'FALSE';
		}

						// If an array or object, JSON encode.
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = implode(
				'，',
				array_map( fn ( $v ) =>  str_replace( '"', '""', $this->stringify_value( $v ) ), (array) $value )
			);
		}

		// If we have null, set to empty string.
		if ( null === $value ) {
			$value = '';
		}

		// Replace any commas with a full-width comma.
		$value = str_replace( ',', '，', $value );

		// Return the value.
		return (string) $value;
	}
}
