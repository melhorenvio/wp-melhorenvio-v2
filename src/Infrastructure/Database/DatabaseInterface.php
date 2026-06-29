<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\Database;

interface DatabaseInterface {

	public function getRow( string $query ): ?array;

	public function getResults( string $query ): array;

	public function getVar( string $query ): mixed;

	public function insert( string $table, array $data ): int;

	public function update( string $table, array $data, array $where ): int;

	public function delete( string $table, array $where ): int;

	public function prepare( string $query, mixed ...$args ): string;

	public function getTableName( string $table ): string;
}
