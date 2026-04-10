<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Q2S_Rewrite {

	public function __construct() {
		add_action( 'init', array( $this, 'register_rewrite_rule' ) );
		add_action( 'init', array( $this, 'maybe_flush_rules' ), 20 );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'parse_request', array( $this, 'resolve_slug' ) );
	}

	/**
	 * Register a single rewrite rule that captures all slugs under the prefix.
	 */
	public function register_rewrite_rule(): void {
		$prefix = self::get_prefix();

		add_rewrite_rule(
			'^' . preg_quote( $prefix, '/' ) . '/([^/]+)/?$',
			'index.php?q2s_slug=$matches[1]',
			'top'
		);
	}

	/**
	 * Register q2s_slug as a recognized query variable.
	 */
	public function register_query_var( array $vars ): array {
		$vars[] = 'q2s_slug';
		return $vars;
	}

	/**
	 * On parse_request, if q2s_slug is set, look up the rule
	 * and inject its filters into the query vars.
	 */
	public function resolve_slug( \WP $wp ): void {
		if ( empty( $wp->query_vars['q2s_slug'] ) ) {
			return;
		}

		$slug = sanitize_title( $wp->query_vars['q2s_slug'] );
		$rule = Q2S_DB::get_rule_by_slug( $slug );

		if ( ! $rule || 1 !== $rule['status'] ) {
			// Remove our query var and flag as 404.
			// Safe because the prefix namespace (e.g. /lp/) is exclusively ours.
			unset( $wp->query_vars['q2s_slug'] );
			$wp->query_vars['error'] = '404';
			return;
		}

		// Remove our internal query var.
		unset( $wp->query_vars['q2s_slug'] );

		// Inject the rule's filters as query vars.
		foreach ( $rule['filters'] as $key => $value ) {
			$wp->query_vars[ $key ] = $value;
		}
	}

	/**
	 * Flush rewrite rules if flagged by a rule save/delete/toggle.
	 */
	public function maybe_flush_rules(): void {
		if ( get_transient( 'q2s_flush_rewrite' ) ) {
			delete_transient( 'q2s_flush_rewrite' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Get the configured URL prefix.
	 */
	public static function get_prefix(): string {
		$prefix = get_option( 'q2s_prefix', 'lp' );
		return sanitize_title( $prefix ) ?: 'lp';
	}
}
