<?php

/**
 * @group ajax
 */
class Test_Q2SLUG_Admin_Ajax extends WP_Ajax_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2SLUG_DB::create_table();
		update_option( 'q2slug_prefix', 'lp' );

		// Register AJAX handlers (normally done by Q2SLUG_Admin constructor).
		$admin = new Q2SLUG_Admin();
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE " . Q2SLUG_DB::table_name() );
		parent::tear_down();
	}

	// -----------------------------------------------------------------
	// q2slug_check_slug
	// -----------------------------------------------------------------

	public function test_check_slug_available(): void {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['slug']        = 'brand-new';
		$_POST['rule_id']     = 0;

		try {
			$this->_handleAjax( 'q2slug_check_slug' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
	}

	public function test_check_slug_taken(): void {
		Q2SLUG_DB::save_rule( array(
			'slug'    => 'taken-slug',
			'filters' => array( 'cat' => 'a' ),
		) );

		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['slug']        = 'taken-slug';
		$_POST['rule_id']     = 0;

		try {
			$this->_handleAjax( 'q2slug_check_slug' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
	}

	public function test_check_slug_nonce_failure(): void {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = 'invalid-nonce';
		$_POST['slug']        = 'test';

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'q2slug_check_slug' );
	}

	public function test_check_slug_no_capability(): void {
		$this->_setRole( 'subscriber' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['slug']        = 'test';

		try {
			$this->_handleAjax( 'q2slug_check_slug' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
	}

	// -----------------------------------------------------------------
	// q2slug_toggle_status
	// -----------------------------------------------------------------

	public function test_toggle_status_success(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'toggle-ajax',
			'filters' => array( 'cat' => 't' ),
			'status'  => 1,
		) );

		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['rule_id']     = $id;

		try {
			$this->_handleAjax( 'q2slug_toggle_status' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertSame( 0, $response['data']['status'] );
	}

	public function test_toggle_status_nonce_failure(): void {
		$this->_setRole( 'administrator' );
		$_POST['_ajax_nonce'] = 'bad';
		$_POST['rule_id']     = 1;

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'q2slug_toggle_status' );
	}

	public function test_toggle_status_no_capability(): void {
		$id = Q2SLUG_DB::save_rule( array(
			'slug'    => 'toggle-nopriv',
			'filters' => array( 'cat' => 'x' ),
			'status'  => 1,
		) );

		$this->_setRole( 'subscriber' );
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['rule_id']     = $id;

		try {
			$this->_handleAjax( 'q2slug_toggle_status' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );

		// Rule should remain unchanged.
		$rule = Q2SLUG_DB::get_rule( $id );
		$this->assertSame( 1, $rule['status'] );
	}

	// -----------------------------------------------------------------
	// Unauthenticated (nopriv) — should not be reachable
	// -----------------------------------------------------------------

	public function test_nopriv_check_slug_rejected(): void {
		$this->logout();
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['slug']        = 'test';

		try {
			$this->_handleAjax( 'q2slug_check_slug' );
		} catch ( WPAjaxDieStopException $e ) {
			// Nonce fails for logged-out user — rejected.
			return;
		} catch ( WPAjaxDieContinueException $e ) {
			// Nonce passes but capability check fails — also rejected.
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
			return;
		}

		$this->fail( 'Expected AJAX request to be rejected for logged-out user.' );
	}

	public function test_nopriv_toggle_status_rejected(): void {
		$this->logout();
		$_POST['_ajax_nonce'] = wp_create_nonce( 'q2slug_admin' );
		$_POST['rule_id']     = 1;

		try {
			$this->_handleAjax( 'q2slug_toggle_status' );
		} catch ( WPAjaxDieStopException $e ) {
			return;
		} catch ( WPAjaxDieContinueException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
			return;
		}

		$this->fail( 'Expected AJAX request to be rejected for logged-out user.' );
	}
}
