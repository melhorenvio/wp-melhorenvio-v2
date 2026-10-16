<?php

declare(strict_types=1);

namespace MelhorEnvio\Providers;

final class ApplicationServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		// Ponto de extensão para bindings da aplicação; hoje tudo é resolvido via autowiring
		// do Container ou registrado no CoreServiceProvider.
	}
}
