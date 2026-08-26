<?php

declare(strict_types=1);

namespace MelhorEnvio\Database\Contracts;

interface DatabaseInterface {

	public function getRow( string $query ): ?array;

	public function getResults( string $query ): array;

	/**
	 * @return mixed
	 */
	public function getVar( string $query );

	public function insert( string $table, array $data ): int;

	public function update( string $table, array $data, array $where ): int;

	public function delete( string $table, array $where ): int;

	/**
	 * @param mixed ...$args
	 */
	public function prepare( string $query, ...$args ): string;

	public function getTableName( string $table ): string;
}
