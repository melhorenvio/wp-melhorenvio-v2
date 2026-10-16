<?php

declare(strict_types=1);

namespace MelhorEnvio\Core;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

final class Container {

	private array $bindings = array();

	private array $instances = array();

	/**
	 * @param Closure|string $concrete
	 */
	public function bind( string $abstract, $concrete ): void {
		$this->bindings[ $abstract ] = $concrete;
	}

	/**
	 * @param Closure|string $concrete
	 */
	public function singleton( string $abstract, $concrete ): void {
		$this->bind( $abstract, $concrete );
		$this->instances[ $abstract ] = null;
	}

	public function get( string $abstract ): object {
		if ( array_key_exists( $abstract, $this->instances ) && $this->instances[ $abstract ] !== null ) {
			return $this->instances[ $abstract ];
		}

		$instance = $this->resolve( $abstract );

		if ( array_key_exists( $abstract, $this->instances ) ) {
			$this->instances[ $abstract ] = $instance;
		}

		return $instance;
	}

	private function resolve( string $abstract ): object {
		if ( array_key_exists( $abstract, $this->bindings ) ) {
			$concrete = $this->bindings[ $abstract ];

			if ( $concrete instanceof Closure ) {
				return $concrete( $this );
			}

			if ( is_string( $concrete ) ) {
				return $this->build( $concrete );
			}
		}

		if ( interface_exists( $abstract ) ) {
			throw new InvalidArgumentException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				"Interface {$abstract} is not bound. Please register it in a ServiceProvider."
			);
		}

		// class_exists() garante que ReflectionClass não lança ReflectionException aqui.
		if ( class_exists( $abstract ) && ( new ReflectionClass( $abstract ) )->isAbstract() ) {
			throw new InvalidArgumentException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				"Abstract class {$abstract} is not bound. Please register it in a ServiceProvider."
			);
		}

		return $this->build( $abstract );
	}

	private function build( string $class ): object {
		try {
			$reflector = new ReflectionClass( $class );
		} catch ( ReflectionException $e ) {
			throw new InvalidArgumentException( "Class {$class} does not exist.", 0, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! $reflector->isInstantiable() ) {
			throw new InvalidArgumentException( "Class {$class} is not instantiable." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$constructor = $reflector->getConstructor();

		if ( $constructor === null ) {
			return new $class();
		}

		$parameters   = $constructor->getParameters();
		$dependencies = $this->resolveDependencies( $parameters );

		return $reflector->newInstanceArgs( $dependencies );
	}

	private function resolveDependencies( array $parameters ): array {
		$dependencies = array();

		foreach ( $parameters as $parameter ) {
			$dependencies[] = $this->resolveParameter( $parameter );
		}

		return $dependencies;
	}

	/**
	 * @return mixed
	 */
	private function resolveParameter( ReflectionParameter $parameter ) {
		$type = $parameter->getType();

		if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {
			$typeName = $type->getName();

			return $typeName === self::class ? $this : $this->get( $typeName );
		}

		if ( $parameter->isDefaultValueAvailable() ) {
			return $parameter->getDefaultValue();
		}

		$declaringClass     = $parameter->getDeclaringClass();
		$declaringClassName = $declaringClass !== null ? $declaringClass->getName() : 'unknown';

		throw new InvalidArgumentException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			"Cannot resolve parameter {$parameter->getName()} in {$declaringClassName}"
		);
	}

	public function has( string $abstract ): bool {
		return array_key_exists( $abstract, $this->bindings ) || class_exists( $abstract );
	}
}
