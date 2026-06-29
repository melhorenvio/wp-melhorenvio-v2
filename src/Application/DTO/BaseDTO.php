<?php

declare(strict_types=1);

namespace MelhorEnvio\Application\DTO;

abstract class BaseDTO {

	abstract public static function fromArray( array $data ): static;

	abstract public function toArray(): array;
}
