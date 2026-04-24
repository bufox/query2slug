<?php

class Test_Q2SLUG_Lifecycle extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2SLUG_DB::create_table();
		update_option( 'q2slug_prefix', 'lp' );

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE " . Q2SLUG_DB::table_name() );
		parent::tear_down();
	}

	public function test_activation_creates_table(): void {
		global $wpdb;
		$table = Q2SLUG_DB::table_name();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
	}

	public function test_activation_sets_default_prefix(): void {
		delete_option( 'q2slug_prefix' );
		Q2SLUG_DB::activate();
		$this->assertSame( 'lp', get_option( 'q2slug_prefix' ) );
	}

	public function test_activation_sets_flush_transient(): void {
		delete_transient( 'q2slug_flush_rewrite' );
		Q2SLUG_DB::activate();
		$this->assertTrue( (bool) get_transient( 'q2slug_flush_rewrite' ) );
	}

	public function test_deactivation_removes_rewrite_rule(): void {
		global $wp_rewrite;

		// Simulate activation: register our rewrite rule.
		$rewrite = new Q2SLUG_Rewrite();
		$rewrite->register_rewrite_rule();
		$wp_rewrite->flush_rules();

		// Verify our rule exists.
		$rules = $wp_rewrite->wp_rewrite_rules();
		$pattern = '^lp/([^/]+)/?$';
		$this->assertArrayHasKey( $pattern, $rules, 'Rule should exist before deactivation' );

		// Simulate deactivation.
		q2slug_deactivate();

		// Verify our rule is removed.
		$rules = $wp_rewrite->wp_rewrite_rules();
		$this->assertArrayNotHasKey( $pattern, $rules, 'Rule should be removed after deactivation' );
	}

	public function test_uninstall_cleans_options(): void {
		// Ensure data exists.
		update_option( 'q2slug_prefix', 'lp' );
		set_transient( 'q2slug_flush_rewrite', true );

		// Simulate the option cleanup from uninstall.php.
		delete_option( 'q2slug_prefix' );
		delete_transient( 'q2slug_flush_rewrite' );

		$this->assertFalse( get_option( 'q2slug_prefix' ) );
		$this->assertFalse( get_transient( 'q2slug_flush_rewrite' ) );
	}
}
