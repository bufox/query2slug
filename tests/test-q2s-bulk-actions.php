<?php

class Test_Q2S_Bulk_Actions extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Q2S_DB::create_table();
		update_option( 'q2s_prefix', 'lp' );

		// Need an admin user for capability checks.
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'TRUNCATE TABLE ' . Q2S_DB::table_name() );
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Helper: simulate a bulk action POST and execute prepare_items().
	 *
	 * process_bulk_action() calls wp_safe_redirect + exit on success.
	 * We intercept the redirect via filter to prevent the exit and
	 * verify the redirect target.
	 */
	private function do_bulk_action( string $action, array $rule_ids ): void {
		$table = new Q2S_Rules_Table();
		$nonce = wp_create_nonce( 'bulk-' . $table->_args['plural'] );

		// current_action() reads $_REQUEST, not $_POST.
		$_REQUEST['action']    = $action;
		$_REQUEST['_wpnonce']  = $nonce;
		$_POST['action']       = $action;
		$_POST['q2s_rule_ids'] = $rule_ids;
		$_POST['_wpnonce']     = $nonce;

		add_filter( 'wp_redirect', function ( $location ) {
			throw new Exception( $location );
		} );

		try {
			$table->prepare_items();
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'message=bulk_done', $e->getMessage() );
			return;
		}

		$this->fail( 'Expected redirect after bulk action.' );
	}

	public function test_bulk_deactivate(): void {
		$id1 = Q2S_DB::save_rule( array( 'slug' => 'bulk-a', 'filters' => array( 'cat' => 'a' ), 'status' => 1 ) );
		$id2 = Q2S_DB::save_rule( array( 'slug' => 'bulk-b', 'filters' => array( 'cat' => 'b' ), 'status' => 1 ) );

		$this->do_bulk_action( 'deactivate', array( $id1, $id2 ) );

		$this->assertSame( 0, Q2S_DB::get_rule( $id1 )['status'] );
		$this->assertSame( 0, Q2S_DB::get_rule( $id2 )['status'] );
	}

	public function test_bulk_activate(): void {
		$id1 = Q2S_DB::save_rule( array( 'slug' => 'bulk-c', 'filters' => array( 'cat' => 'c' ), 'status' => 0 ) );
		$id2 = Q2S_DB::save_rule( array( 'slug' => 'bulk-d', 'filters' => array( 'cat' => 'd' ), 'status' => 0 ) );

		$this->do_bulk_action( 'activate', array( $id1, $id2 ) );

		$this->assertSame( 1, Q2S_DB::get_rule( $id1 )['status'] );
		$this->assertSame( 1, Q2S_DB::get_rule( $id2 )['status'] );
	}

	public function test_bulk_delete(): void {
		$id1 = Q2S_DB::save_rule( array( 'slug' => 'bulk-e', 'filters' => array( 'cat' => 'e' ), 'status' => 1 ) );
		$id2 = Q2S_DB::save_rule( array( 'slug' => 'bulk-f', 'filters' => array( 'cat' => 'f' ), 'status' => 1 ) );
		$keep = Q2S_DB::save_rule( array( 'slug' => 'bulk-keep', 'filters' => array( 'cat' => 'keep' ), 'status' => 1 ) );

		$this->do_bulk_action( 'delete', array( $id1, $id2 ) );

		$this->assertNull( Q2S_DB::get_rule( $id1 ) );
		$this->assertNull( Q2S_DB::get_rule( $id2 ) );
		$this->assertNotNull( Q2S_DB::get_rule( $keep ) );
	}

	public function test_bulk_no_action_loads_items(): void {
		Q2S_DB::save_rule( array( 'slug' => 'no-action', 'filters' => array( 'cat' => 'x' ), 'status' => 1 ) );

		$table = new Q2S_Rules_Table();
		$table->prepare_items();

		$this->assertCount( 1, $table->items );
		$this->assertSame( 'no-action', $table->items[0]['slug'] );
	}

	public function test_bulk_requires_capability(): void {
		$id = Q2S_DB::save_rule( array( 'slug' => 'cap-test', 'filters' => array( 'cat' => 'z' ), 'status' => 1 ) );

		// Create nonce as admin (valid nonce), then switch to subscriber.
		$table = new Q2S_Rules_Table();
		$nonce = wp_create_nonce( 'bulk-' . $table->_args['plural'] );

		$sub_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $sub_id );

		$_REQUEST['action']    = 'delete';
		$_REQUEST['_wpnonce']  = $nonce;
		$_POST['action']       = 'delete';
		$_POST['q2s_rule_ids'] = array( $id );
		$_POST['_wpnonce']     = $nonce;

		$this->expectException( WPDieException::class );
		$table->prepare_items();
	}

	public function test_bulk_empty_ids_does_nothing(): void {
		$id = Q2S_DB::save_rule( array( 'slug' => 'empty-ids', 'filters' => array( 'cat' => 'q' ), 'status' => 1 ) );

		$table = new Q2S_Rules_Table();
		$nonce = wp_create_nonce( 'bulk-' . $table->_args['plural'] );

		$_REQUEST['action']    = 'delete';
		$_REQUEST['_wpnonce']  = $nonce;
		$_POST['action']       = 'delete';
		$_POST['q2s_rule_ids'] = array();
		$_POST['_wpnonce']     = $nonce;

		$table->prepare_items();

		$this->assertNotNull( Q2S_DB::get_rule( $id ) );
	}
}
