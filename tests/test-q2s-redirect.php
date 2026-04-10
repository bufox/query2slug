<?php

/**
 * Exception used to intercept wp_redirect() calls without triggering exit.
 */
class Q2S_Redirect_Caught extends Exception {
	public string $location;
	public int $status;

	public function __construct( string $location, int $status ) {
		$this->location = $location;
		$this->status   = $status;
		parent::__construct( "Redirect to {$location} ({$status})" );
	}
}

class Test_Q2S_Redirect extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2S_DB::create_table();
		update_option( 'q2s_prefix', 'lp' );

		// Intercept wp_redirect so maybe_redirect() doesn't call exit.
		add_filter( 'wp_redirect', function ( string $location, int $status ): string {
			throw new Q2S_Redirect_Caught( $location, $status );
		}, 1, 2 );
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE " . Q2S_DB::table_name() );
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Helper: simulate $_GET and call maybe_redirect(), catch the redirect.
	 */
	private function do_redirect( array $get_params ): Q2S_Redirect_Caught {
		$_GET    = $get_params;
		$redirect = new Q2S_Redirect();

		try {
			$redirect->maybe_redirect();
		} catch ( Q2S_Redirect_Caught $e ) {
			return $e;
		}

		$this->fail( 'Expected redirect was not triggered.' );
	}

	/**
	 * Helper: simulate $_GET and verify no redirect happens.
	 */
	private function assert_no_redirect( array $get_params ): void {
		$_GET    = $get_params;
		$redirect = new Q2S_Redirect();

		try {
			$redirect->maybe_redirect();
		} catch ( Q2S_Redirect_Caught $e ) {
			$this->fail( 'Unexpected redirect to ' . $e->location );
		}

		// If we get here, no redirect happened — test passes.
		$this->assertTrue( true );
	}

	public function test_tracking_params_list(): void {
		// Verify the tracking params constant contains the expected entries.
		$this->assertContains( 'utm_source', Q2S_Redirect::TRACKING_PARAMS );
		$this->assertContains( 'fbclid', Q2S_Redirect::TRACKING_PARAMS );
		$this->assertContains( 'gclid', Q2S_Redirect::TRACKING_PARAMS );
	}

	public function test_filter_hash_order_independent(): void {
		$hash_a = Q2S_DB::compute_filter_hash( array( 'cat' => 't-shirt', 'tag' => 'disney' ) );
		$hash_b = Q2S_DB::compute_filter_hash( array( 'tag' => 'disney', 'cat' => 't-shirt' ) );

		$this->assertSame( $hash_a, $hash_b );
	}

	public function test_filter_hash_different_for_different_filters(): void {
		$hash_a = Q2S_DB::compute_filter_hash( array( 'cat' => 't-shirt' ) );
		$hash_b = Q2S_DB::compute_filter_hash( array( 'cat' => 'hoodie' ) );

		$this->assertNotSame( $hash_a, $hash_b );
	}

	public function test_rule_lookup_by_filters_order_independent(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'order-test',
			'filters' => array( 'product_tag' => 'disney', 'product_cat' => 't-shirt' ),
			'status'  => 1,
		) );

		// Look up with reversed order.
		$rule = Q2S_DB::get_rule_by_filters( array( 'product_cat' => 't-shirt', 'product_tag' => 'disney' ) );
		$this->assertNotNull( $rule );
		$this->assertSame( 'order-test', $rule['slug'] );
	}

	public function test_inactive_rule_not_matched_for_redirect(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'inactive-redirect',
			'filters' => array( 'cat' => 'test' ),
			'status'  => 0,
		) );

		// get_rule_by_filters only returns active rules.
		$rule = Q2S_DB::get_rule_by_filters( array( 'cat' => 'test' ) );
		$this->assertNull( $rule );
	}

	// -----------------------------------------------------------------
	// Full redirect tests (intercept wp_redirect)
	// -----------------------------------------------------------------

	public function test_redirect_triggers_301_to_canonical_url(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'tshirt-disney',
			'filters' => array( 'category_name' => 't-shirt', 'tag' => 'disney' ),
			'status'  => 1,
		) );

		$caught = $this->do_redirect( array( 'category_name' => 't-shirt', 'tag' => 'disney' ) );

		$this->assertSame( 301, $caught->status );
		$this->assertStringContainsString( '/lp/tshirt-disney/', $caught->location );
	}

	public function test_redirect_order_independent(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'order-redirect',
			'filters' => array( 'category_name' => 't-shirt', 'tag' => 'disney' ),
			'status'  => 1,
		) );

		// Reversed order in $_GET.
		$caught = $this->do_redirect( array( 'tag' => 'disney', 'category_name' => 't-shirt' ) );

		$this->assertSame( 301, $caught->status );
		$this->assertStringContainsString( '/lp/order-redirect/', $caught->location );
	}

	public function test_redirect_preserves_utm_params(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'utm-test',
			'filters' => array( 'category_name' => 'felpe' ),
			'status'  => 1,
		) );

		$caught = $this->do_redirect( array(
			'category_name' => 'felpe',
			'utm_source'    => 'google',
			'utm_medium'    => 'cpc',
			'fbclid'        => 'abc123',
		) );

		$this->assertSame( 301, $caught->status );
		$this->assertStringContainsString( '/lp/utm-test/', $caught->location );
		$this->assertStringContainsString( 'utm_source=google', $caught->location );
		$this->assertStringContainsString( 'utm_medium=cpc', $caught->location );
		$this->assertStringContainsString( 'fbclid=abc123', $caught->location );
	}

	public function test_redirect_url_encoded_values(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'encoded-test',
			'filters' => array( 'product_tag' => 'walt-disney' ),
			'status'  => 1,
		) );

		// %2D is a URL-encoded hyphen.
		$caught = $this->do_redirect( array( 'product_tag' => 'walt%2Ddisney' ) );

		$this->assertSame( 301, $caught->status );
		$this->assertStringContainsString( '/lp/encoded-test/', $caught->location );
	}

	public function test_no_redirect_when_no_matching_rule(): void {
		$this->assert_no_redirect( array( 'category_name' => 'nonexistent' ) );
	}

	public function test_no_redirect_when_inactive_rule(): void {
		Q2S_DB::save_rule( array(
			'slug'    => 'inactive-full',
			'filters' => array( 'tag' => 'hidden' ),
			'status'  => 0,
		) );

		$this->assert_no_redirect( array( 'tag' => 'hidden' ) );
	}

	public function test_no_redirect_when_only_tracking_params(): void {
		$this->assert_no_redirect( array( 'utm_source' => 'google', 'fbclid' => 'abc' ) );
	}

	public function test_no_redirect_when_empty_get(): void {
		$this->assert_no_redirect( array() );
	}
}
