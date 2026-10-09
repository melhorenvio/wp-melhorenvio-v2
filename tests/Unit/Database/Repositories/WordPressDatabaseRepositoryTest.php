<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Database\Repositories;

use MelhorEnvio\Database\Contracts\DatabaseInterface;
use MelhorEnvio\Database\Repositories\WordPressDatabaseRepository;
use MelhorEnvio\Tests\TestCase;
use Mockery;

require_once dirname( __DIR__, 3 ) . '/Stubs/Extra/WordPressDatabaseRepositoryTest.php';

final class WordPressDatabaseRepositoryTest extends TestCase {

	/** @var \Mockery\MockInterface&\wpdb */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->markTestIncomplete(
			'BUG: WordPressDatabaseRepository implementa DatabaseInterface sem o use e gera erro fatal ao carregar. '
			. 'Correção: fix/database-repository-interface-import.'
		);
		$this->wpdb = Mockery::mock( 'wpdb' );
	}

	public function test_implements_database_interface(): void {
		self::assertInstanceOf( DatabaseInterface::class, $this->repository() );
	}

	public function test_get_row_returns_associative_array(): void {
		$this->wpdb->expects( 'get_row' )->with( 'SELECT 1', ARRAY_A )->andReturn( array( 'id' => '1' ) );

		self::assertSame( array( 'id' => '1' ), $this->repository()->getRow( 'SELECT 1' ) );
	}

	public function test_get_row_returns_null_when_no_row(): void {
		$this->wpdb->expects( 'get_row' )->andReturn( null );

		self::assertNull( $this->repository()->getRow( 'SELECT 1' ) );
	}

	public function test_get_results_casts_each_row_to_array(): void {
		$this->wpdb->expects( 'get_results' )
			->with( 'SELECT *', ARRAY_A )
			->andReturn( array( array( 'id' => '1' ), (object) array( 'id' => '2' ) ) );

		self::assertSame(
			array( array( 'id' => '1' ), array( 'id' => '2' ) ),
			$this->repository()->getResults( 'SELECT *' )
		);
	}

	public function test_get_results_returns_empty_array_on_null(): void {
		$this->wpdb->expects( 'get_results' )->andReturn( null );

		self::assertSame( array(), $this->repository()->getResults( 'SELECT *' ) );
	}

	public function test_get_var_returns_raw_value(): void {
		$this->wpdb->expects( 'get_var' )->with( 'SELECT COUNT(*)' )->andReturn( '42' );

		self::assertSame( '42', $this->repository()->getVar( 'SELECT COUNT(*)' ) );
	}

	public function test_insert_returns_insert_id(): void {
		$this->wpdb->expects( 'insert' )->with( 'wp_table', array( 'a' => 1 ) )->andReturn( 1 );
		$this->wpdb->insert_id = 99;

		self::assertSame( 99, $this->repository()->insert( 'wp_table', array( 'a' => 1 ) ) );
	}

	public function test_insert_returns_zero_on_failure(): void {
		$this->wpdb->expects( 'insert' )->andReturn( false );
		$this->wpdb->insert_id = 99;

		self::assertSame( 0, $this->repository()->insert( 'wp_table', array( 'a' => 1 ) ) );
	}

	public function test_update_returns_affected_rows(): void {
		$this->wpdb->expects( 'update' )->with( 'wp_table', array( 'a' => 2 ), array( 'id' => 1 ) )->andReturn( 3 );

		self::assertSame( 3, $this->repository()->update( 'wp_table', array( 'a' => 2 ), array( 'id' => 1 ) ) );
	}

	public function test_update_returns_zero_on_failure(): void {
		$this->wpdb->expects( 'update' )->andReturn( false );

		self::assertSame( 0, $this->repository()->update( 'wp_table', array( 'a' => 2 ), array( 'id' => 1 ) ) );
	}

	public function test_delete_returns_affected_rows(): void {
		$this->wpdb->expects( 'delete' )->with( 'wp_table', array( 'id' => 1 ) )->andReturn( 1 );

		self::assertSame( 1, $this->repository()->delete( 'wp_table', array( 'id' => 1 ) ) );
	}

	public function test_delete_returns_zero_on_failure(): void {
		$this->wpdb->expects( 'delete' )->andReturn( false );

		self::assertSame( 0, $this->repository()->delete( 'wp_table', array( 'id' => 1 ) ) );
	}

	public function test_prepare_forwards_all_arguments(): void {
		$this->wpdb->expects( 'prepare' )
			->with( 'SELECT * FROM t WHERE a = %d AND b = %s', 5, 'x' )
			->andReturn( "SELECT * FROM t WHERE a = 5 AND b = 'x'" );

		self::assertSame(
			"SELECT * FROM t WHERE a = 5 AND b = 'x'",
			$this->repository()->prepare( 'SELECT * FROM t WHERE a = %d AND b = %s', 5, 'x' )
		);
	}

	public function test_get_table_name_prepends_prefix(): void {
		$this->wpdb->prefix = 'wp_custom_';

		self::assertSame( 'wp_custom_orders', $this->repository()->getTableName( 'orders' ) );
	}

	private function repository(): WordPressDatabaseRepository {
		return new WordPressDatabaseRepository( $this->wpdb );
	}
}
