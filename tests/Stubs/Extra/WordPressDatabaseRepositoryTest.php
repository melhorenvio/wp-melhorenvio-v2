<?php
// phpcs:ignoreFile
/**
 * Minimal wpdb stand-in so WordPressDatabaseRepository's `wpdb` type hint resolves.
 * Mock it with Mockery::mock( 'wpdb' ) and set ->prefix / ->insert_id as needed.
 */

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {

		/** @var string */
		public $prefix = 'wp_';

		/** @var int */
		public $insert_id = 0;

		/** @return mixed */
		public function get_row( $query = null, $output = 'OBJECT', $y = 0 ) {
			return null;
		}

		/** @return mixed */
		public function get_results( $query = null, $output = 'OBJECT' ) {
			return null;
		}

		/** @return mixed */
		public function get_var( $query = null, $x = 0, $y = 0 ) {
			return null;
		}

		/** @return int|false */
		public function insert( $table, $data, $format = null ) {
			return false;
		}

		/** @return int|false */
		public function update( $table, $data, $where, $format = null, $where_format = null ) {
			return false;
		}

		/** @return int|false */
		public function delete( $table, $where, $where_format = null ) {
			return false;
		}

		/** @return string|void */
		public function prepare( $query, ...$args ) {
			return $query;
		}
	}
}
