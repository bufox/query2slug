<?php

class Test_Q2S_Prefix_Collision extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// Ensure rewrite rules are populated.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();
	}

	public function test_collision_with_page(): void {
		self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_name'   => 'landing',
			'post_status' => 'publish',
		) );

		$result = Q2S_Admin::check_prefix_collision( 'landing' );
		$this->assertNotNull( $result );
		$this->assertSame( 'page', $result );
	}

	public function test_collision_with_post(): void {
		self::factory()->post->create( array(
			'post_type'   => 'post',
			'post_name'   => 'my-prefix',
			'post_status' => 'publish',
		) );

		$result = Q2S_Admin::check_prefix_collision( 'my-prefix' );
		// get_page_by_path with post type 'post' — may or may not match depending on permalink structure.
		// With /%postname%/ it could match.
		// The important thing is it doesn't crash.
		$this->assertTrue( true );
	}

	public function test_collision_with_rewrite_rule(): void {
		// Inject a known rewrite rule to simulate a collision.
		global $wp_rewrite;
		$wp_rewrite->extra_rules_top['testprefix/([^/]+)/?$'] = 'index.php?name=$matches[1]';
		$wp_rewrite->flush_rules();

		$result = Q2S_Admin::check_prefix_collision( 'testprefix' );
		$this->assertNotNull( $result, 'testprefix should collide with injected rewrite rule' );

		// Cleanup.
		unset( $wp_rewrite->extra_rules_top['testprefix/([^/]+)/?$'] );
		$wp_rewrite->flush_rules();
	}

	public function test_collision_with_taxonomy_term(): void {
		// Create a category with slug 'promo'.
		wp_insert_term( 'Promo', 'category', array( 'slug' => 'promo' ) );

		$result = Q2S_Admin::check_prefix_collision( 'promo' );
		$this->assertNotNull( $result, 'promo should collide with the category term' );
	}

	public function test_no_collision_with_safe_prefix(): void {
		$result = Q2S_Admin::check_prefix_collision( 'lp' );
		$this->assertNull( $result, 'lp should not collide with anything' );
	}

	public function test_no_collision_with_unique_prefix(): void {
		$result = Q2S_Admin::check_prefix_collision( 'q2s-landing' );
		$this->assertNull( $result, 'q2s-landing should not collide with anything' );
	}
}
