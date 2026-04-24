<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Q2SLUG_DB {

	const TABLE_NAME   = 'q2slug_rules';
	const CACHE_GROUP  = 'q2slug';

	/**
	 * Get the full table name with prefix.
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Plugin activation: create table and set default options.
	 */
	public static function activate(): void {
		self::create_table();

		if ( false === get_option( 'q2slug_prefix' ) ) {
			add_option( 'q2slug_prefix', 'lp' );
		}

		set_transient( 'q2slug_flush_rewrite', true );
	}

	/**
	 * Plugin deactivation: flush rewrite rules to remove ours.
	 *
	 * Note: the caller must ensure our rewrite rule hook is removed
	 * before calling this, otherwise flush will re-add our rule.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Create the rules table using dbDelta.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(200) NOT NULL,
			filters text NOT NULL,
			filter_hash varchar(64) NOT NULL,
			status tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			UNIQUE KEY filter_hash (filter_hash)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Compute a deterministic hash for a set of filters.
	 *
	 * Filters are sorted by key, then JSON-encoded and hashed.
	 * This provides O(1) lookup for canonical redirect matching.
	 */
	public static function compute_filter_hash( array $filters ): string {
		ksort( $filters );
		return hash( 'sha256', wp_json_encode( $filters ) );
	}

	/**
	 * Invalidate all object caches for rules.
	 */
	private static function invalidate_cache(): void {
		wp_cache_delete( 'all', self::CACHE_GROUP );
		wp_cache_delete( 'active', self::CACHE_GROUP );
		wp_cache_delete( 'inactive', self::CACHE_GROUP );
		// Individual slug/id/hash caches are flushed by group convention.
		// Since wp_cache doesn't support group flush in all backends,
		// we increment a generation counter.
		$gen = (int) wp_cache_get( 'generation', self::CACHE_GROUP );
		wp_cache_set( 'generation', $gen + 1, self::CACHE_GROUP );
	}

	/**
	 * Get a cache key scoped to the current generation.
	 */
	private static function cache_key( string $key ): string {
		$gen = (int) wp_cache_get( 'generation', self::CACHE_GROUP );
		return $key . ':' . $gen;
	}

	/**
	 * Get all rules, optionally filtered by status.
	 *
	 * @param int|null $status 1 for active, 0 for inactive, null for all.
	 * @return array
	 */
	public static function get_rules( ?int $status = null ): array {
		global $wpdb;

		$cache_key = self::cache_key( null === $status ? 'all' : ( $status ? 'active' : 'inactive' ) );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$table = self::table_name();

		if ( null !== $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cached below.
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %d ORDER BY created_at DESC", $status ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cached below.
			$results = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
		}

		$rules = array_map( array( self::class, 'hydrate_rule' ), $results ?: array() );
		wp_cache_set( $cache_key, $rules, self::CACHE_GROUP );

		return $rules;
	}

	/**
	 * Get a single rule by slug.
	 */
	public static function get_rule_by_slug( string $slug ): ?array {
		global $wpdb;

		$cache_key = self::cache_key( 'slug:' . $slug );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ?: null; // Empty array = cache miss stored as "not found".
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cached below.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ) );

		$rule = $row ? self::hydrate_rule( $row ) : null;
		wp_cache_set( $cache_key, $rule ?: array(), self::CACHE_GROUP );

		return $rule;
	}

	/**
	 * Get a single rule by its filter hash (for canonical redirect lookup).
	 */
	public static function get_rule_by_filters( array $filters ): ?array {
		global $wpdb;

		$hash      = self::compute_filter_hash( $filters );
		$cache_key = self::cache_key( 'hash:' . $hash );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cached below.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE filter_hash = %s AND status = 1", $hash ) );

		$rule = $row ? self::hydrate_rule( $row ) : null;
		wp_cache_set( $cache_key, $rule ?: array(), self::CACHE_GROUP );

		return $rule;
	}

	/**
	 * Get a single rule by ID.
	 */
	public static function get_rule( int $id ): ?array {
		global $wpdb;

		$cache_key = self::cache_key( 'id:' . $id );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cached below.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		$rule = $row ? self::hydrate_rule( $row ) : null;
		wp_cache_set( $cache_key, $rule ?: array(), self::CACHE_GROUP );

		return $rule;
	}

	/**
	 * Save a rule (insert or update).
	 *
	 * @param array $data {
	 *     @type int|null $id      Rule ID for update, null for insert.
	 *     @type string   $slug    URL slug.
	 *     @type array    $filters Key/value filter pairs.
	 *     @type int      $status  1 for active, 0 for inactive.
	 * }
	 * @return int|WP_Error Rule ID on success, WP_Error on failure.
	 */
	public static function save_rule( array $data ): int|\WP_Error {
		global $wpdb;

		$table = self::table_name();
		$id    = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
		$slug  = isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '';

		if ( empty( $slug ) ) {
			return new \WP_Error( 'q2slug_empty_slug', __( 'Slug cannot be empty.', 'query2slug' ) );
		}

		if ( ! preg_match( '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $slug ) ) {
			return new \WP_Error( 'q2slug_invalid_slug', __( 'Slug must contain only lowercase letters, numbers, and hyphens.', 'query2slug' ) );
		}

		$filters = isset( $data['filters'] ) && is_array( $data['filters'] ) ? $data['filters'] : array();

		if ( empty( $filters ) ) {
			return new \WP_Error( 'q2slug_empty_filters', __( 'At least one filter is required.', 'query2slug' ) );
		}

		// Sanitize filter keys and values.
		$clean_filters = array();
		foreach ( $filters as $key => $value ) {
			$clean_key   = sanitize_text_field( $key );
			$clean_value = sanitize_text_field( $value );
			if ( '' !== $clean_key && '' !== $clean_value ) {
				$clean_filters[ $clean_key ] = $clean_value;
			}
		}

		if ( empty( $clean_filters ) ) {
			return new \WP_Error( 'q2slug_empty_filters', __( 'At least one valid filter is required.', 'query2slug' ) );
		}

		$filter_hash = self::compute_filter_hash( $clean_filters );
		$status      = isset( $data['status'] ) ? absint( $data['status'] ) : 1;

		// Check slug uniqueness (excluding current rule if updating).
		$existing = self::get_rule_by_slug( $slug );
		if ( $existing && $existing['id'] !== $id ) {
			return new \WP_Error( 'q2slug_duplicate_slug', __( 'This slug is already in use.', 'query2slug' ) );
		}

		// Check filter hash uniqueness.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Write path, no cache needed.
		$hash_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE filter_hash = %s AND id != %d", $filter_hash, $id ) );
		if ( $hash_exists ) {
			return new \WP_Error( 'q2slug_duplicate_filters', __( 'A rule with the same filters already exists.', 'query2slug' ) );
		}

		$db_data = array(
			'slug'        => $slug,
			'filters'     => wp_json_encode( $clean_filters ),
			'filter_hash' => $filter_hash,
			'status'      => $status,
		);

		$formats = array( '%s', '%s', '%s', '%d' );

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cache invalidated below.
			$wpdb->update( $table, $db_data, array( 'id' => $id ), $formats, array( '%d' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cache invalidated below.
			$wpdb->insert( $table, $db_data, $formats );
			$id = (int) $wpdb->insert_id;
		}

		self::invalidate_cache();
		set_transient( 'q2slug_flush_rewrite', true );

		return $id;
	}

	/**
	 * Delete a rule by ID.
	 */
	public static function delete_rule( int $id ): bool {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cache invalidated below.
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $result ) {
			self::invalidate_cache();
			set_transient( 'q2slug_flush_rewrite', true );
		}

		return (bool) $result;
	}

	/**
	 * Set a rule's status to a specific value.
	 */
	public static function set_status( int $id, int $status ): bool {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cache invalidated below.
		$result = $wpdb->update( $table, array( 'status' => $status ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );

		if ( false !== $result ) {
			self::invalidate_cache();
			set_transient( 'q2slug_flush_rewrite', true );
		}

		return false !== $result;
	}

	/**
	 * Toggle a rule's status.
	 */
	public static function toggle_status( int $id ): bool {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, cache invalidated below.
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 1 - status WHERE id = %d", $id ) );

		if ( $result ) {
			self::invalidate_cache();
			set_transient( 'q2slug_flush_rewrite', true );
		}

		return (bool) $result;
	}

	/**
	 * Hydrate a DB row into an associative array with decoded filters.
	 */
	private static function hydrate_rule( object $row ): array {
		return array(
			'id'         => (int) $row->id,
			'slug'       => $row->slug,
			'filters'    => json_decode( $row->filters, true ) ?: array(),
			'status'     => (int) $row->status,
			'created_at' => $row->created_at,
			'updated_at' => $row->updated_at,
		);
	}
}
