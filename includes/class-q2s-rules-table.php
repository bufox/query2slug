<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Q2S_Rules_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'rule',
			'plural'   => 'rules',
			'ajax'     => false,
		) );
	}

	/**
	 * Fetch and prepare rules for display.
	 */
	public function prepare_items(): void {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
			$this->get_primary_column_name(),
		);

		$this->items = Q2S_DB::get_rules();

		$this->process_bulk_action();
	}

	/**
	 * Define table columns.
	 */
	public function get_columns(): array {
		return array(
			'cb'      => '<input type="checkbox" />',
			'slug'    => __( 'Slug', 'query2slug' ),
			'url'     => __( 'URL', 'query2slug' ),
			'filters' => __( 'Filters', 'query2slug' ),
		);
	}

	/**
	 * The primary column for responsive toggling.
	 */
	protected function get_primary_column_name(): string {
		return 'slug';
	}

	/**
	 * Checkbox column.
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="q2s_rule_ids[]" value="%d" />',
			$item['id']
		);
	}

	/**
	 * Slug column with status toggle and row actions.
	 */
	public function column_slug( array $item ): string {
		$edit_url   = admin_url( 'admin.php?page=q2s-edit&rule_id=' . $item['id'] );
		$delete_url = wp_nonce_url(
			admin_url( 'admin.php?page=q2s-rules&q2s_action=delete&rule_id=' . $item['id'] ),
			'q2s_delete_' . $item['id']
		);

		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'query2slug' )
			),
			'delete' => sprintf(
				'<a href="%s" class="q2s-delete" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Delete this rule?', 'query2slug' ) ),
				esc_html__( 'Delete', 'query2slug' )
			),
		);

		$icon_class = $item['status'] ? 'dashicons-yes-alt q2s-status-active' : 'dashicons-marker q2s-status-inactive';
		$icon_title = $item['status']
			? esc_attr__( 'Active — click to deactivate', 'query2slug' )
			: esc_attr__( 'Inactive — click to activate', 'query2slug' );

		return sprintf(
			'<button type="button" class="q2s-toggle-status button-link" data-rule-id="%d" title="%s"><span class="dashicons %s"></span></button> <strong>%s</strong>%s',
			$item['id'],
			$icon_title,
			$icon_class,
			esc_html( $item['slug'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * URL column.
	 */
	public function column_url( array $item ): string {
		$prefix = Q2S_Rewrite::get_prefix();
		$url    = home_url( '/' . $prefix . '/' . $item['slug'] . '/' );

		return sprintf(
			'<a href="%s" target="_blank" class="q2s-url-cell">%s</a>',
			esc_url( $url ),
			esc_html( $url )
		);
	}

	/**
	 * Filters column — badges.
	 */
	public function column_filters( array $item ): string {
		$badges = '';
		foreach ( $item['filters'] as $key => $value ) {
			$badges .= sprintf(
				'<span class="q2s-filter-badge"><span class="q2s-filter-key">%s</span>=%s</span> ',
				esc_html( $key ),
				esc_html( $value )
			);
		}
		return $badges;
	}

	/**
	 * Define bulk actions.
	 */
	public function get_bulk_actions(): array {
		return array(
			'activate'   => __( 'Activate', 'query2slug' ),
			'deactivate' => __( 'Deactivate', 'query2slug' ),
			'delete'     => __( 'Delete', 'query2slug' ),
		);
	}

	/**
	 * Handle bulk actions.
	 */
	public function process_bulk_action(): void {
		// Bulk actions are handled in Q2S_Admin::handle_form_submissions().
	}

	/**
	 * Message when no rules exist.
	 */
	public function no_items(): void {
		?>
		<div class="q2s-empty-state">
			<span class="dashicons dashicons-admin-links"></span>
			<p><?php esc_html_e( 'No rules yet. Create your first rule to get started.', 'query2slug' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=q2s-edit' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add New Rule', 'query2slug' ); ?>
			</a>
		</div>
		<?php
	}
}
