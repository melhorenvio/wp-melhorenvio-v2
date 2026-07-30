<?php

declare(strict_types=1);

namespace MelhorEnvio\Application\DTO;

abstract class BaseDTO {

	/**
	 * @return static
	 */
	abstract public static function fromArray( array $data );

	abstract public function toArray(): array;
}
