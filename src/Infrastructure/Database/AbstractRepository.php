<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\Database;

use MelhorEnvio\Application\Repository\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface {

	protected DatabaseInterface $database;

	protected string $tableName;

	public function __construct( DatabaseInterface $database ) {
		$this->database  = $database;
		$this->tableName = $this->getTableName();
	}

	abstract protected function getTableName(): string;

	abstract protected function mapToEntity( array $row ): object;

	abstract protected function mapToRow( object $entity ): array;

	public function findById( int $id ): ?object {
		$row = $this->database->getRow(
			$this->database->prepare(
				"SELECT * FROM {$this->database->getTableName($this->tableName)} WHERE id = %d",
				$id
			)
		);

		if ( $row === null ) {
			return null;
		}

		return $this->mapToEntity( $row );
	}

	public function findAll(): array {
		$rows = $this->database->getResults(
			"SELECT * FROM {$this->database->getTableName($this->tableName)}"
		);

		return array_map( array( $this, 'mapToEntity' ), $rows );
	}

	public function save( object $entity ): object {
		$row = $this->mapToRow( $entity );

		if ( isset( $row['id'] ) && $row['id'] > 0 ) {
			$this->database->update(
				$this->database->getTableName( $this->tableName ),
				$row,
				array( 'id' => $row['id'] )
			);
		} else {
			unset( $row['id'] );
			$id        = $this->database->insert(
				$this->database->getTableName( $this->tableName ),
				$row
			);
			$row['id'] = $id;
		}

		return $this->mapToEntity( $row );
	}

	public function delete( object $entity ): bool {
		if ( ! isset( $entity->id ) || $entity->id <= 0 ) {
			return false;
		}

		return $this->database->delete(
			$this->database->getTableName( $this->tableName ),
			array( 'id' => $entity->id )
		) > 0;
	}
}
