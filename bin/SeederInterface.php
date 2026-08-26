<?php

declare(strict_types=1);

namespace MelhorEnvio\Database\Contracts;

interface SeederInterface {

	public function run(): void;
}
