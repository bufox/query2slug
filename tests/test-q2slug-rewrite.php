<?php

class Test_Q2SLUG_Rewrite extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2SLUG_DB::create_table();
		update_option( 'q2slug_prefix', 'lp' );
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE " . Q2SLUG_DB::table_name() );
		parent::tear_down();
	}

	public function test_query_var_registered(): void {
		$rewrite = new Q2SLUG_Rewrite();
		$vars    = $rewrite->register_query_var( array() );

		$this->assertContains( 'q2slug_slug', $vars );
	}

	public function test_get_prefix_default(): void {
		delete_option( 'q2slug_prefix' );
		$this->assertSame( 'lp', Q2SLUG_Rewrite::get_prefix() );
	}

	public function test_get_prefix_custom(): void {
		update_option( 'q2slug_prefix', 'go' );
		$this->assertSame( 'go', Q2SLUG_Rewrite::get_prefix() );
	}

	public function test_resolve_slug_injects_filters(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'disney-tshirts',
			'filters' => array( 'product_cat' => 't-shirt', 'product_tag' => 'disney' ),
			'status'  => 1,
		) );

		$rewrite = new Q2SLUG_Rewrite();

		// Simulate a WP object with q2slug_slug set.
		$wp = new WP();
		$wp->query_vars = array( 'q2slug_slug' => 'disney-tshirts' );

		$rewrite->resolve_slug( $wp );

		$this->assertArrayNotHasKey( 'q2slug_slug', $wp->query_vars );
		$this->assertSame( 't-shirt', $wp->query_vars['product_cat'] );
		$this->assertSame( 'disney', $wp->query_vars['product_tag'] );
	}

	public function test_resolve_slug_inactive_rule_ignored(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'inactive-rule',
			'filters' => array( 'cat' => 'test' ),
			'status'  => 0,
		) );

		$rewrite = new Q2SLUG_Rewrite();
		$wp      = new WP();
		$wp->query_vars = array( 'q2slug_slug' => 'inactive-rule' );

		$rewrite->resolve_slug( $wp );

		// Should force a 404.
		$this->assertSame( '404', $wp->query_vars['error'] );
		$this->assertArrayNotHasKey( 'cat', $wp->query_vars );
	}

	public function test_resolve_slug_unknown_slug_returns_404(): void {
		$rewrite = new Q2SLUG_Rewrite();
		$wp      = new WP();
		$wp->query_vars = array( 'q2slug_slug' => 'does-not-exist' );

		$rewrite->resolve_slug( $wp );

		// Should force a 404.
		$this->assertSame( '404', $wp->query_vars['error'] );
	}

	public function test_resolve_slug_no_slug_noop(): void {
		$rewrite = new Q2SLUG_Rewrite();
		$wp      = new WP();
		$wp->query_vars = array( 'page' => '1' );

		$rewrite->resolve_slug( $wp );

		// Nothing should change.
		$this->assertSame( array( 'page' => '1' ), $wp->query_vars );
	}

	public function test_prefix_change_affects_resolution(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'prefix-test',
			'filters' => array( 'cat' => 'news' ),
			'status'  => 1,
		) );

		// With default prefix 'lp', slug resolves.
		$rewrite = new Q2SLUG_Rewrite();
		$wp      = new WP();
		$wp->query_vars = array( 'q2slug_slug' => 'prefix-test' );
		$rewrite->resolve_slug( $wp );
		$this->assertSame( 'news', $wp->query_vars['cat'] );

		// Change prefix — resolve_slug still works (it doesn't check prefix,
		// the rewrite rule regex does). But get_prefix returns the new value.
		update_option( 'q2slug_prefix', 'go' );
		$this->assertSame( 'go', Q2SLUG_Rewrite::get_prefix() );
	}

	public function test_empty_prefix_falls_back_to_default(): void {
		update_option( 'q2slug_prefix', '' );
		$this->assertSame( 'lp', Q2SLUG_Rewrite::get_prefix() );
	}
}
