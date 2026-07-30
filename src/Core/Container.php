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
			throw new InvalidArgumentException(
				"Interface {$abstract} is not bound. Please register it in a ServiceProvider."
			);
		}

		if ( class_exists( $abstract ) ) {
			try {
				$reflector = new ReflectionClass( $abstract );
				if ( $reflector->isAbstract() ) {
					throw new InvalidArgumentException(
						"Abstract class {$abstract} is not bound. Please register it in a ServiceProvider."
					);
				}
			} catch ( ReflectionException $e ) {
			}
		}

		return $this->build( $abstract );
	}

	private function build( string $class ): object {
		try {
			$reflector = new ReflectionClass( $class );
		} catch ( ReflectionException $e ) {
			throw new InvalidArgumentException( "Class {$class} does not exist.", 0, $e );
		}

		if ( ! $reflector->isInstantiable() ) {
			throw new InvalidArgumentException( "Class {$class} is not instantiable." );
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
			$type = $parameter->getType();

			if ( $type === null || ! $type instanceof \ReflectionNamedType || $type->isBuiltin() ) {
				if ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					$declaringClass    = $parameter->getDeclaringClass();
					$declaringClassName = $declaringClass !== null ? $declaringClass->getName() : 'unknown';

					throw new InvalidArgumentException(
						"Cannot resolve parameter {$parameter->getName()} in {$declaringClassName}"
					);
				}
			} else {
				$typeName = $type->getName();
				if ( $typeName === self::class ) {
					$dependencies[] = $this;
				} else {
					$dependencies[] = $this->get( $typeName );
				}
			}
		}

		return $dependencies;
	}

	public function has( string $abstract ): bool {
		return array_key_exists( $abstract, $this->bindings ) || class_exists( $abstract );
	}
}
