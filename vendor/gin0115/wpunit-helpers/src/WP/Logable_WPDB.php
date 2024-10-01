<?php

declare(strict_types=1);

/**
 * An instance of WPDB where all queries are logged internally.
 *
 * @package PinkCrab\Test_Helpers
 * @author Glynn Quelch glynn@pinkcrab.co.uk
 * @since 0.0.1
 */

namespace Gin0115\WPUnit_Helpers\WP;

class Logable_WPDB extends \wpdb {


	/** @var array<string,mixed[]> */
	public $usage_log = array();

	/**
	 * Sets the value to return from the next call.
	 *
	 * @var mixed
	 */
	public $then_return = null;

	/**
	 * Ignore the constructor!
	 *
	 * @param null $a
	 * @param null $b
	 * @param null $c
	 * @param null $d
	 */
	public function __construct( $a = null, $b = null, $c = null, $d = null ) {
		$this->void( $a );
		$this->void( $b );
		$this->void( $c );
		$this->void( $d );
	}

	/**
	 * A function that doest nothing.
	 *
	 * @param mixed $a
	 * @return void
	 */
	private function void( $a ): void {
		$val = $a;
	}

	/**
	 * Logs any calls made to insert
	 *
	 * NATIVE RETURN >> The number of rows inserted, or false on error.
	 *
	 * @param string $table
	 * @param mixed[] $data
	 * @param mixed[]|string|null $format
	 * @return mixed
	 */
	public function insert( $table, $data, $format = null ) {
		$this->usage_log['insert'][ $table ][] = array(
			'data'   => $data,
			'format' => $format,
		);

		return $this->then_return;
	}

	/**
	 * Logs any get_results call.
	 *
	 * NATIVE RETURN >> array|object|null Database query results.
	 *
	 * @param string $query  SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K
	 * @return mixed
	 */
	public function get_results( $query = null, $output = OBJECT ) {
		$this->usage_log['get_results'][] = array(
			'query'  => $query,
			'output' => $output,
		);

		return $this->then_return;
	}

	/**
	 * Logs any prepare call.
	 *
	 * NATIVE RETURN >> string The query with populated placeholders.
	 *
	 * @param string $query
	 * @param mixed ...$args
	 * @return string
	 */
	public function prepare( $query, ...$args ) {
		$this->usage_log['prepare'][] = array(
			'query' => $query,
			'args'  => $args[0],
		);

		return sprintf( \str_replace( '%s', "'%s'", $query ), ...$args[0] );
	}

	/**
	 * Logs every Query call
	 *
	 * NATIVE RETURN >> int|bool
	 *
	 * @param string $query
	 * @return int|bool
	 */
	public function query( $query ) {
		$this->usage_log['query'][] = $query;

		return $this->then_return;
	}

	/**
	 * Logs every replace call
	 *
	 * NATIVE RETURN >> int|bool
	 *
	 * @param string $table
	 * @param mixed[] $data
	 * @param mixed[] $format
	 * @return int|bool
	 */
	public function replace( $table, $data, $format = null ) {
		$this->usage_log['replace'][ $table ][] = array(
			'data'   => $data,
			'format' => $format,
		);

		return $this->then_return;
	}

	/**
	 * Logs every update call
	 *
	 * NATIVE RETURN >> int|bool
	 *
	 * @param string $table
	 * @param mixed[] $data
	 * @param mixed[] $where
	 * @param mixed[] $format
	 * @param mixed[] $where_format
	 * @return int|bool
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$this->usage_log['update'][ $table ][] = array(
			'data'         => $data,
			'where'        => $where,
			'format'       => $format,
			'where_format' => $where_format,
		);

		return $this->then_return;
	}

	/**
	 * Logs every delete call
	 *
	 * NATIVE RETURN >> int|bool
	 *
	 * @param string $table
	 * @param mixed[] $where
	 * @param mixed[] $where_format
	 * @return int|bool
	 */
	public function delete( $table, $where, $where_format = null ) {
		$this->usage_log['delete'][ $table ][] = array(
			'where'        => $where,
			'where_format' => $where_format,
		);

		return $this->then_return;
	}

	/**
	 * Logs every get_var call
	 *
	 * NATIVE RETURN >> string|null
	 *
	 * @param string $query
	 * @param int    $x     Optional. Column of value to return. Indexed from 0.
	 * @param int    $y     Optional. Row of value to return. Indexed from 0.
	 * @return string|null
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->usage_log['get_var'][] = array(
			'query' => $query,
			'x'     => $x,
			'y'     => $y,
		);

		return $this->then_return;
	}

	/**
	 * Logs every get_row call
	 *
	 * NATIVE RETURN >> string|null
	 *
	 * @param string $query
	 * @param string $output
	 * @param int    $y     Optional. Row of value to return. Indexed from 0.
	 * @return string|null
	 */
	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->usage_log['get_row'][] = array(
			'query'  => $query,
			'output' => $output,
			'y'      => $y,
		);

		return $this->then_return;
	}

	/**
	 * Logs every get_col call
	 *
	 * NATIVE RETURN >> array
	 *
	 * @param string $query
	 * @param int    $x     Optional. Column of value to return. Indexed from 0.
	 * @return mixed[]
	 */
	public function get_col( $query = null, $x = 0 ) {
		$this->usage_log['get_col'][] = array(
			'query' => $query,
			'x'     => $x,
		);

		return $this->then_return;
	}
}
