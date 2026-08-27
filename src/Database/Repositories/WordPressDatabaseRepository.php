<?php

declare(strict_types=1);

namespace MelhorEnvio\Database\Repositories;

use wpdb;

final class WordPressDatabaseRepository implements DatabaseInterface {

	private wpdb $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function getRow( string $query ): ?array {
		$result = $this->wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $result !== null ? (array) $result : null;
	}

	public function getResults( string $query ): array {
		$results = $this->wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $results === null ) {
			return array();
		}

		return array_map(
			function ( $result ) {
				return (array) $result;
			},
			$results
		);
	}

	/**
	 * @return mixed
	 */
	public function getVar( string $query ) {
		return $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public function insert( string $table, array $data ): int {
		$result = $this->wpdb->insert( $table, $data );

		if ( $result === false ) {
			return 0;
		}

		return (int) $this->wpdb->insert_id;
	}

	public function update( string $table, array $data, array $where ): int {
		$result = $this->wpdb->update( $table, $data, $where );

		return $result !== false ? $result : 0;
	}

	public function delete( string $table, array $where ): int {
		$result = $this->wpdb->delete( $table, $where );

		return $result !== false ? $result : 0;
	}

	/**
	 * @param mixed ...$args
	 */
	public function prepare( string $query, ...$args ): string {
		return $this->wpdb->prepare( $query, ...$args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public function getTableName( string $table ): string {
		return $this->wpdb->prefix . $table;
	}
}
