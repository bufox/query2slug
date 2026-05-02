<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Q2SLUG_Redirect {

	/**
	 * Query parameter keys that are tracking/UTM params, not content filters.
	 * These are stripped before matching and re-appended after redirect.
	 */
	const TRACKING_PARAMS = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_content',
		'utm_term',
		'utm_id',
		'fbclid',
		'gclid',
		'gclsrc',
		'dclid',
		'msclkid',
		'twclid',
		'li_fat_id',
		'mc_cid',
		'mc_eid',
		'_ga',
		'_gl',
	);

	public function __construct() {
		// Priority 5: run before WordPress canonical redirect (priority 10).
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 5 );
	}

	/**
	 * If the current request's query params match a rule, redirect to the canonical slug URL.
	 *
	 * Nonce verification does not apply here: this is a public-facing canonical
	 * redirect that runs on every front-end request. Visitors arriving from
	 * external sources (paid ads, search engines, shared links) cannot carry
	 * nonces. The handler performs no state changes — it only inspects
	 * sanitized query parameters and, on a match, issues a 301 to the canonical
	 * URL. This mirrors WordPress core's own redirect_canonical() behavior.
	 */
	public function maybe_redirect(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public canonical redirect, see method docblock.
		if ( empty( $_GET ) ) {
			return;
		}

		// Separate tracking params from content filters.
		$tracking_params = array();
		$filter_params   = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public canonical redirect, see method docblock.
		$raw_params = wp_unslash( $_GET );

		foreach ( $raw_params as $key => $value ) {
			if ( ! is_string( $key ) || ! is_string( $value ) ) {
				continue;
			}

			$clean_key   = sanitize_text_field( $key );
			$clean_value = sanitize_text_field( $value );

			if ( in_array( strtolower( $clean_key ), self::TRACKING_PARAMS, true ) ) {
				$tracking_params[ $clean_key ] = $clean_value;
			} else {
				$filter_params[ $clean_key ] = $clean_value;
			}
		}

		if ( empty( $filter_params ) ) {
			return;
		}

		$rule = Q2SLUG_DB::get_rule_by_filters( $filter_params );

		if ( ! $rule ) {
			return;
		}

		$prefix       = Q2SLUG_Rewrite::get_prefix();
		$canonical_url = home_url( '/' . $prefix . '/' . $rule['slug'] . '/' );

		// Re-append tracking params if any.
		if ( ! empty( $tracking_params ) ) {
			$canonical_url = add_query_arg( $tracking_params, $canonical_url );
		}

		wp_safe_redirect( $canonical_url, 301 );
		exit;
	}
}
