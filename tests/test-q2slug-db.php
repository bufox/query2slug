<?php

class Test_Q2SLUG_DB extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2SLUG_DB::create_table();
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE " . Q2SLUG_DB::table_name() );
		parent::tear_down();
	}

	public function test_create_table(): void {
		global $wpdb;
		$table = Q2SLUG_DB::table_name();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
	}

	public function test_save_and_get_rule(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'test-slug',
			'filters' => array( 'product_cat' => 't-shirt', 'product_tag' => 'disney' ),
			'status'  => 1,
		) );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertNotNull( $rule );
		$this->assertSame( 'test-slug', $rule['slug'] );
		$this->assertSame( array( 'product_cat' => 't-shirt', 'product_tag' => 'disney' ), $rule['filters'] );
		$this->assertSame( 1, $rule['status'] );
	}

	public function test_get_rule_by_slug(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'my-slug',
			'filters' => array( 'category_name' => 'news' ),
		) );

		$rule = Q2SLUG_DB::get_rule_by_slug( 'my-slug' );
		$this->assertNotNull( $rule );
		$this->assertSame( 'my-slug', $rule['slug'] );

		$this->assertNull( Q2SLUG_DB::get_rule_by_slug( 'nonexistent' ) );
	}

	public function test_get_rule_by_filters(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'filter-test',
			'filters' => array( 'product_tag' => 'disney', 'product_cat' => 't-shirt' ),
		) );

		// Order-independent lookup.
		$rule = Q2SLUG_DB::get_rule_by_filters( array( 'product_cat' => 't-shirt', 'product_tag' => 'disney' ) );
		$this->assertNotNull( $rule );
		$this->assertSame( 'filter-test', $rule['slug'] );

		// No match.
		$this->assertNull( Q2SLUG_DB::get_rule_by_filters( array( 'product_cat' => 'hoodie' ) ) );
	}

	public function test_slug_uniqueness(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'unique-slug',
			'filters' => array( 'cat' => 'a' ),
		) );

		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'unique-slug',
			'filters' => array( 'cat' => 'b' ),
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'q2slug_duplicate_slug', $result->get_error_code() );
	}

	public function test_filter_uniqueness(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'slug-a',
			'filters' => array( 'cat' => 'same' ),
		) );

		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'slug-b',
			'filters' => array( 'cat' => 'same' ),
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'q2slug_duplicate_filters', $result->get_error_code() );
	}

	public function test_empty_slug_rejected(): void {
		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => '',
			'filters' => array( 'cat' => 'a' ),
		) );

		$this->assertWPError( $result );
	}

	public function test_empty_filters_rejected(): void {
		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'valid-slug',
			'filters' => array(),
		) );

		$this->assertWPError( $result );
	}

	public function test_delete_rule(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'to-delete',
			'filters' => array( 'cat' => 'x' ),
		) );

		$this->assertTrue( Q2SLUG_DB::delete_rule( $id ) );
		$this->assertNull( Q2SLUG_DB::get_rule( $id ) );
	}

	public function test_toggle_status(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'toggle-me',
			'filters' => array( 'cat' => 'y' ),
			'status'  => 1,
		) );

		Q2SLUG_DB::toggle_status( $id );
		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertSame( 0, $rule['status'] );

		Q2SLUG_DB::toggle_status( $id );
		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertSame( 1, $rule['status'] );
	}

	public function test_get_rules_filtered_by_status(): void {
		Q2SLUG_DB::save_rule( array( 'slug' => 'active-1', 'filters' => array( 'a' => '1' ), 'status' => 1 ) );
		Q2SLUG_DB::save_rule( array( 'slug' => 'active-2', 'filters' => array( 'a' => '2' ), 'status' => 1 ) );
		Q2SLUG_DB::save_rule( array( 'slug' => 'inactive-1', 'filters' => array( 'a' => '3' ), 'status' => 0 ) );

		$all      = Q2SLUG_DB::get_rules();
		$active   = Q2SLUG_DB::get_rules( 1 );
		$inactive = Q2SLUG_DB::get_rules( 0 );

		$this->assertCount( 3, $all );
		$this->assertCount( 2, $active );
		$this->assertCount( 1, $inactive );
	}

	public function test_update_rule(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'original',
			'filters' => array( 'cat' => 'old' ),
		) );

		Q2SLUG_DB::save_rule( array(
			'id'      => $id,
			'slug'    => 'updated',
			'filters' => array( 'cat' => 'new' ),
		) );

		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertSame( 'updated', $rule['slug'] );
		$this->assertSame( array( 'cat' => 'new' ), $rule['filters'] );
	}

	public function test_slug_with_accents_sanitized(): void {
		// sanitize_title converts accented chars: "königin" → something ASCII-safe.
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'königin-elsa',
			'filters' => array( 'tag' => 'frozen' ),
		) );

		$this->assertIsInt( $id );
		$rule = Q2SLUG_DB::get_rule( $id );
		// sanitize_title strips or transliterates non-ASCII.
		$this->assertMatchesRegularExpression( '/^[a-z0-9-]+$/', $rule['slug'] );
	}

	public function test_slug_uppercase_normalized(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'T-Shirt-Disney',
			'filters' => array( 'cat' => 'upper' ),
		) );

		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertSame( 't-shirt-disney', $rule['slug'] );
	}

	public function test_filters_with_empty_value_discarded(): void {
		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'empty-val',
			'filters' => array( 'cat' => 't-shirt', 'tag' => '', 'brand' => 'disney' ),
		) );

		$this->assertIsInt( $result );
		$rule = Q2SLUG_DB::get_rule( $result );
		// Empty value should be stripped, only cat and brand remain.
		$this->assertArrayNotHasKey( 'tag', $rule['filters'] );
		$this->assertSame( 't-shirt', $rule['filters']['cat'] );
		$this->assertSame( 'disney', $rule['filters']['brand'] );
	}

	public function test_filters_with_empty_key_discarded(): void {
		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'empty-key',
			'filters' => array( '' => 'value', 'cat' => 'valid' ),
		) );

		$this->assertIsInt( $result );
		$rule = Q2SLUG_DB::get_rule( $result );
		$this->assertCount( 1, $rule['filters'] );
		$this->assertSame( 'valid', $rule['filters']['cat'] );
	}

	public function test_filters_all_empty_rejected(): void {
		$result = Q2SLUG_DB::save_rule( array(
			'slug'    => 'all-empty',
			'filters' => array( '' => '', 'also-empty' => '' ),
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'q2slug_empty_filters', $result->get_error_code() );
	}

	public function test_bulk_activate(): void {
		$id1 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-a', 'filters' => array( 'a' => '1' ), 'status' => 0 ) );
		$id2 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-b', 'filters' => array( 'a' => '2' ), 'status' => 0 ) );
		$id3 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-c', 'filters' => array( 'a' => '3' ), 'status' => 0 ) );

		global $wpdb;
		$table = Q2SLUG_DB::table_name();
		foreach ( array( $id1, $id2, $id3 ) as $id ) {
			$wpdb->update( $table, array( 'status' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		}

		$this->assertSame( 1, Q2SLUG_DB::get_rule( $id1 )['status'] );
		$this->assertSame( 1, Q2SLUG_DB::get_rule( $id2 )['status'] );
		$this->assertSame( 1, Q2SLUG_DB::get_rule( $id3 )['status'] );
	}

	public function test_bulk_deactivate(): void {
		$id1 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-d', 'filters' => array( 'b' => '1' ), 'status' => 1 ) );
		$id2 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-e', 'filters' => array( 'b' => '2' ), 'status' => 1 ) );

		global $wpdb;
		$table = Q2SLUG_DB::table_name();
		foreach ( array( $id1, $id2 ) as $id ) {
			$wpdb->update( $table, array( 'status' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		}

		$this->assertSame( 0, Q2SLUG_DB::get_rule( $id1 )['status'] );
		$this->assertSame( 0, Q2SLUG_DB::get_rule( $id2 )['status'] );
	}

	public function test_bulk_delete(): void {
		$id1 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-f', 'filters' => array( 'c' => '1' ), 'status' => 1 ) );
		$id2 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-g', 'filters' => array( 'c' => '2' ), 'status' => 1 ) );
		$id3 = Q2SLUG_DB::save_rule( array( 'slug' => 'bulk-h', 'filters' => array( 'c' => '3' ), 'status' => 1 ) );

		foreach ( array( $id1, $id2 ) as $id ) {
			Q2SLUG_DB::delete_rule( $id );
		}

		$this->assertNull( Q2SLUG_DB::get_rule( $id1 ) );
		$this->assertNull( Q2SLUG_DB::get_rule( $id2 ) );
		// id3 should still exist.
		$this->assertNotNull( Q2SLUG_DB::get_rule( $id3 ) );
	}
}
