<?php

declare(strict_types=1);

namespace MelhorEnvio\Database\Contracts;

interface RepositoryInterface {

	public function findById( int $id ): ?object;

	public function findAll(): array;

	public function save( object $entity ): object;

	public function delete( object $entity ): bool;
}
