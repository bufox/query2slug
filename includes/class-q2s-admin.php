<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Q2S_Admin {

	/** @var string Main menu page hook suffix. */
	private string $hook_list = '';

	/** @var string Edit page hook suffix. */
	private string $hook_edit = '';

	/** @var string Settings page hook suffix. */
	private string $hook_settings = '';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_q2s_check_slug', array( $this, 'ajax_check_slug' ) );
		add_action( 'wp_ajax_q2s_toggle_status', array( $this, 'ajax_toggle_status' ) );
		add_action( 'wp_ajax_q2s_get_taxonomies', array( $this, 'ajax_get_taxonomies' ) );
		add_action( 'wp_ajax_q2s_get_terms', array( $this, 'ajax_get_terms' ) );
		add_action( 'wp_ajax_q2s_check_prefix', array( $this, 'ajax_check_prefix' ) );
	}

	/**
	 * Register top-level menu and subpages.
	 */
	public function add_menu_pages(): void {
		$this->hook_list = add_menu_page(
			__( 'Query2Slug', 'query2slug' ),
			__( 'Query2Slug', 'query2slug' ),
			'manage_options',
			'q2s-rules',
			array( $this, 'render_list_page' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9Ii0yMzEgLTM4OCA3MDQgMzMyIiBmaWxsPSJibGFjayIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj4KICA8cGF0aCBkPSJtIDMxLjQ0OTc2NCwtMTQyLjQ5ODYgYyAwLC02LjIxMzM1IDguMzE4NjUsLTIzLjU2NzU3IDE3LjY1MzQ1LC0zNi44MjgzIDEyLjc0NDMyLC0xOC4xMDQyMSAyNy43NjAyODEsLTMyLjE2NzU2IDYwLjM0NjU1NiwtNTYuNTE4MjEgMTIuOTI1LC05LjY1ODQzIDI1Ljk3MTU0LC0xOS40MDg0MyAyOC45OTIzLC0yMS42NjY2NiAyOC42Mjg5OCwtMjEuNDAyMjIgNDEuMzk4NzgsLTMzLjY2NzUxIDQ3LjI2OTA4LC00NS40MDE1NCA0LjQwMzk4LC04LjgwMzAzIDUuNDA5NzgsLTE0LjYyNzQ2IDMuNzgyMDEsLTIxLjkwMTA3IC0yLjQ2ODQ1LC0xMS4wMzAxOSAtMTIuMjc3NDgsLTE3LjMxNzk3IC0yNi45NzQ4OSwtMTcuMjkxNDEgLTIxLjc0MDExLC0wLjA1NTggLTMzLjAxMjMsMTIuNjQ3ODIgLTQxLjYzMjQ2LDMwLjQ4ODEzIC0yMC40MjUxMiwwLjA2NDIgLTUyLjI0MTE0MywtMC4wMTcgLTUyLjI0MTE0MywtMC4wMTcgMjIuODE0OTIzLC00OC4wMzgyOCA1OC4xNzYyMTMsLTc3LjQ0NjUyIDExMi4yNDI4NTMsLTczLjUzNTg2IDIxLjI2OTQ5LDIuMjkwMDEgMzcuNDExNzksOC45MTE1OSA0OC40Mjg2NSwxOS44NjU0NSAxMy4zMjY5MywxMy4yNTA3IDE4Ljg3MjkzLDMwLjQxMzQ4IDE2LjE2NTI5LDUwLjAyNTQ3IC00LjA4NTg2LDI5LjU5NDc2IC0yMC43NDc5MSw1MC43OTU3NSAtNjQuMTI5MDYsODEuNTk4NTkgLTM0LjYwMjI5LDI0LjU2OTM5IC00My42NDg5NCwzMS40MTE5NiAtNTEuMDk2MzcsMzguNjQ3NDkgLTQuMjkzNDQsNC4xNzEyNyAtNy44MDYyNiw4LjEwODc3IC03LjgwNjI2LDguNzUgMCwwLjg3MjI3IDEyLjU4NDg5LDEuMTY1ODYgNDkuOTc0MzIsMS4xNjU4NiBoIDQ5Ljk3NDMyIGwgNy44ODA3MiwwLjAwNiBjIC0yLjU1NzYxLDQuNTM5NjEgLTYuMTQ5MDMsNy44MDM5NSAtOS45NTIzMywxMS4yMzA5MSAtMTQuOTMxMzIsMTMuNDUzODQgLTI5LjU4NDA1LDIyLjA2ODQ5IC00Ni45MjE2NCwyNy41ODYyMiAtMTguNjY0NDMsNS45NCAtMjEuODIxOTksNi4xNzAxNCAtODQuNzA1Mzk1LDYuMTczNjUgbCAtNTcuMjUwMDAxLDAuMDAzIHoiLz4KICA8cGF0aCBkPSJtIDM2NS43OTc4NywtMTg0LjYwODQ4IGMgMCwwIDMxLjU5OTk4LC04LjgyMTM0IDMyLjg1NzksLTI2Ljg0NjgyIC0wLjEwMjQ5LC0xMi44MjgxMSAtOS4yNDUwOCwtMTguOTgyMTEgLTQyLjcwNiwtMjguNzQ2IC0yOC4xNTU0NSwtOC4yMTU3NSAtNDAuOTA2MjYsLTEzLjEyMTU5IC01MiwtMjAuMDA2ODUgLTIwLjQ5NjU2LC0xMi43MjEwNyAtMjkuMDIxLC0zMi45NTY4MyAtMjQuMDAzMTMsLTU2Ljk3OTg3IDYuNjQ0NDIsLTMxLjgxMDE3IDMyLjgzNjE2LC01Ni43MDMxOSA2OS44NjUzLC02Ni40MDEwMiA0NC40NjEyMSwtMTEuNjQ0MjYgODYuNjQwNTcsLTAuNDQ1ODYgMTIyLjc1NDk5LDE5LjEwNDk5IC0wLjAwNSwwLjA2OTcgLTIyLjQyMDYsNDMuNjM2NjQgLTIyLjQyMDYsNDMuNjM2NjQgLTkuNjU2NzMsLTYuNTg2OTQgLTM0LjExNzc5LC0xNi41MDg4NCAtNDguNzQzMjgsLTE5LjI4OTk5IC0xMi42MDUxOCwtMi4zOTY5NyAtMzAuNjU4ODgsLTIuMDM3MTMgLTM4LjQ5MzE4LC0wLjAwMyAtMTcuMDMwNzksNC40MjA4NyAtMjYuNDYwMSwxMi44MTEzNSAtMjYuNDYwMSwyMy41NDUgMCw1LjQwOTc2IDIuODYyNjQsOS45NjYwNCA4LjY4NDIsMTMuODIyMDggNS41ODkwNiwzLjcwMjA0IDExLjY2MjQsNi4wMjUwOSAyOS4zMTU4LDExLjIxMzI5IDQ2LjE3OTM4LDEzLjU3MTc2IDY0LjE5ODY2LDIyLjkzNTIzIDc0Ljg1OTE5LDM4Ljg5OTQ5IDYuNTcxODcsOS44NDE0NSA4LjQ3NDc4LDE3LjYxNjQ0IDcuOTAzNDMsMzIuMjkyMiAtMC4zOTI3OSwxMC4wODk0MiAtMC45Nzk0LDEzLjY0MDIzIC0zLjQxNjYsMjAuNjgwODEgLTcuNTQwNjksMjEuNzgzNTcgLTIzLjk1MDY4LDM5LjkxMDgyIC00Ni4yMjAyOCw1MS4wNTcxMSAtMTkuODc3NzcsMTAuMjI5NjkgLTQyLjMxMzkzLDEwLjc3OTUxIC02NC4xNTAzNywxMS4wNjg2NyB6Ii8+CiAgPHBhdGggZD0ibSAtNzIuODUzMzg0LC0xMDkuMzY3NjYgYyAtMC4yODYsLTEuMjM3NSAtMS4zMTIyLC03Ljc0ODcgLTIuMjgwNCwtMTQuNDY5MzMgLTAuOTY4MywtNi43MjA2MyAtMi4wNjY5LC0xMi43MTUwMSAtMi40NDEzLC0xMy4zMjA4NiAtMC40ODc1LC0wLjc4ODggLTIuNzk0OSwtMC42NjQ4NiAtOC4xMjczLDAuNDM2NTggLTI1LjY5MTI5Niw1LjMwNjcgLTU2LjY3MzQ5NiwzLjk0Mzk1IC03OC45OTgyOTYsLTMuNDc0NzMgLTMzLjc5NDQsLTExLjIzMDA5IC01NS45NzA3LC0zNC42MDM2OCAtNjQuNDQyNSwtNjcuOTIxNjYgLTIuOTAwMywtMTEuNDA2MTggLTIuNjcwNSwtNDAuOTAwODcgMC40MTk3LC01My44NzMyOCAxMy42OTQzLC01Ny40ODcgNTguNzg3OSwtMTAzLjIwNjc1IDExNi41MjE1LC0xMTguMTM5MyA0MC4xNjgyOTYsLTEwLjM4OTM2IDc4LjY2ODE5NCwtNi4zNjM4MyAxMDguMTUxNzQ0MywxMS4zMDgzIDkuMDQ5MTcsNS40MjM5OCAyMi4zMTk0Njk3LDE4LjE1NTU1IDI4LjQwMDY1OTcsMjcuMjQ3NjggNS40MzkwNSw4LjEzMjA2IDEwLjI0NDI2LDE5LjA1MTgzIDEzLjMyNTEzLDMwLjI4MTE3IDEuNzg5MSw2LjUyMTAyIDIuMTExNTYsMTAuNTMzMSAyLjE0Mzk4LDI2LjY3NTQzIDAuMDQxNywyMC43NjA3MiAtMS4wOTI1NCwyNy44ODE3MSAtNy40ODQyNSw0Ni45ODc4NSAtMi45MzAyOCw4Ljc1OTIgLTEzLjUyMzI2LDMwLjUxMjE1IC0xNC44NTg0NCwzMC41MTIxNSAtMC41OTI2OSwwIC0yMC44Mzk2Nzk3LC04LjkyNTUgLTQwLjU1NTcsLTE3Ljg3ODIyIGwgLTcuMDI4NjMsLTMuMTkxNTggNC43NjkyLC05LjcxNTEgYyA2LjkwNzQzLC0xNC4wNzA4MiA5LjgzMzA1LC0yNS4xNTM2NSAxMC40ODM4OSwtMzkuNzE1MSAwLjY2MDk3LC0xNC43ODgyMiAtMC41MTI3NSwtMjEuNTMyMTQgLTUuNTY5MzEsLTMyIC0xMS40MzE4OCwtMjMuNjY1NyAtNDAuMDE4MTgsLTM1LjA1MTg0IC03Mi40NzUyNzgsLTI4Ljg2NzQxIC00NS45MDI2OTYsOC43NDYzNiAtODAuOTg4NDk2LDQ5LjEwMTY4IC04My4zNDkyOTYsOTUuODY3NDEgLTEuNjE0NSwzMS45ODE1NyAxMy4zMzE4LDUzLjYxNTEyIDQyLjE5ODEsNjEuMDc4MjUgMTQuODAwMiwzLjgyNjQ4IDUwLjIzMjQ5NiwwLjkyNzQ3IDQ5Ljg0NTE5NiwtNC4wNzgyNSAtMC4wODUsLTEuMSAtMS42MTI3LC0xMi42OTczOCAtMy4zOTQ3LC0yNS43NzE5NiAtMS43ODIsLTEzLjA3NDU3IC0zLjAxNTcsLTIzLjk5NjE4IC0yLjc0MTcsLTI0LjI3MDI0IDAuMjc0MSwtMC4yNzQwNiAzLjgyNjcsMS4wNDA1NCA3Ljg5NDcsMi45MjEzMyA5LjczMTUsNC40OTkxOCAzOC4wNDMxOTgsMTcuMzU1ODkgNDkuMzk2NDk4LDIyLjQzMTU4IDQuOTQ5OTUsMi4yMTI5OCAxOS4xMjQ5NSw4LjYxMTggMzEuNDk5OTUwMywxNC4yMTk1OSAxMi4zNzQ5OTk3LDUuNjA3NzkgMjMuMzAzOTk5NywxMC40OTg2IDI0LjI4NjY2OTcsMTAuODY4NDggMS40MzU1MSwwLjU0MDMyIDAuMDU5NywyLjA2NDg5IC03LDcuNzU2OTMgLTE4LjIzMTUzOTcsMTQuNjk5NTcgLTU4LjU5MzkyLDQ3LjI4NTE3IC03Mi41NzgxMiw1OC41OTQyOSAtNy45OTEyLDYuNDYyNSAtMTQuNzQ1OTk4LDExLjc1IC0xNS4wMTA1OTgsMTEuNzUgLTAuMjY0NiwwIC0wLjcxNTEsLTEuMDEyNSAtMS4wMDExLC0yLjI1IHoiLz4KICA8cGF0aCBkPSJtIC0yLjc3NTMzMjgsLTE0OC40ODUzNSBjIDY0LjUwMjEwNjgsNjUuOTU5NzM5IDE0OS4zMjM1MDI4LDQ0LjQzNTQ4IDIwMy4wMjMyNzI4LDE5LjY1ODgzIDEzLjU2MDkyLC02LjI1NjkyIDM5LjUyNTQ5LC0yMS4xMTQ2NyA1MC44OTMsLTI5Ljc1Mzk5IC00LjAzMjkyLC02LjQ4MDY3IC01LjQ2NTY5LC04LjcwMDg0IC0xMC40NjA0OCwtMTYuMTc1MTEgLTMuMzc4MTcsLTUuMDU1MTIgLTYuOTUzNDYsLTEwLjIwMjc2IC02LjQ3NTc4LC0xMC4yMDE5NyAxMC4xMzc3OSwwLjAxNjcgMTAuMTE4MDIsMC4wMTk4IDQ2LjAwNTE2LDAuMTg2MTkgMTYuNTM1NjcsMC4wNzY3IDMzLjIyMzczLDAuMTI0ODMgMzcuMDg0NTcsMC4xMDcwNSAxMy41MjM0NCwtMC4wNjIzIDM4LjM4MjUxLC0wLjE1NzE1IDM4LjM4MjUxLC0wLjE1NzE1IDAsMCAtNS4zOTIwNCwxMS4xNTY4NiAtMTIuMDYwNTUsMjMuNjIyNzUgLTYuNjY4NTEsMTIuNDY1ODkgLTE4LjM3NTYzLDM0LjQxNTU0IC0yNi4wMTU4Miw0OC43NzcgbCAtMTMuODkxMjcsMjYuMTExNzIyIC0xMC4wMjE3NiwtMTEuNjc0NyAtMTAuMDIxNzUsLTExLjY3NDY4MiBjIC0xMDcuNTk0ODksNjIuMzY5ODYgLTIxNS45OTQ3NzQsNzYuNzQxMDMgLTMxNC4yOTY5NDgsNS4xODcwNSBsIC0xMy4zNTIzOCwtMTAuNDc5NTIiLz4KPC9zdmc+Cg==',
			30
		);

		// Override the auto-generated first submenu item label.
		add_submenu_page(
			'q2s-rules',
			__( 'All Rules', 'query2slug' ),
			__( 'All Rules', 'query2slug' ),
			'manage_options',
			'q2s-rules',
			array( $this, 'render_list_page' )
		);

		$this->hook_edit = add_submenu_page(
			'q2s-rules',
			__( 'Add Rule', 'query2slug' ),
			__( 'Add Rule', 'query2slug' ),
			'manage_options',
			'q2s-edit',
			array( $this, 'render_edit_page' )
		);

		$this->hook_settings = add_submenu_page(
			'q2s-rules',
			__( 'Settings', 'query2slug' ),
			__( 'Settings', 'query2slug' ),
			'manage_options',
			'q2s-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin JS/CSS only on our pages.
	 */
	public function enqueue_assets( string $hook ): void {
		$our_hooks = array( $this->hook_list, $this->hook_edit, $this->hook_settings );

		if ( ! in_array( $hook, $our_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'q2s-admin',
			plugins_url( 'assets/admin.css', Q2S_PLUGIN_FILE ),
			array(),
			Q2S_VERSION
		);

		if ( $hook === $this->hook_edit ) {
			wp_enqueue_script(
				'q2s-edit',
				plugins_url( 'assets/edit.js', Q2S_PLUGIN_FILE ),
				array( 'jquery', 'jquery-ui-autocomplete' ),
				Q2S_VERSION,
				true
			);

			wp_localize_script( 'q2s-edit', 'q2sEdit', array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'q2s_admin' ),
				'prefix'   => Q2S_Rewrite::get_prefix(),
				'homeUrl'  => home_url( '/' ),
				'strings'  => array(
					'available' => __( 'Available', 'query2slug' ),
					'taken'     => __( 'This slug is already in use', 'query2slug' ),
					'checking'  => __( 'Checking...', 'query2slug' ),
					'sanitized' => __( 'Slug was automatically cleaned up', 'query2slug' ),
				),
			) );
		}

		if ( $hook === $this->hook_list ) {
			wp_enqueue_script(
				'q2s-list',
				plugins_url( 'assets/list.js', Q2S_PLUGIN_FILE ),
				array( 'jquery' ),
				Q2S_VERSION,
				true
			);

			wp_localize_script( 'q2s-list', 'q2sList', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'q2s_admin' ),
			) );
		}

		if ( $hook === $this->hook_settings ) {
			wp_enqueue_script(
				'q2s-settings',
				plugins_url( 'assets/settings.js', Q2S_PLUGIN_FILE ),
				array( 'jquery' ),
				Q2S_VERSION,
				true
			);

			wp_localize_script( 'q2s-settings', 'q2sSettings', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'q2s_admin' ),
				'homeUrl' => home_url( '/' ),
				'strings' => array(
					'ok'       => __( 'No conflicts detected', 'query2slug' ),
					/* translators: %s: type of conflict (e.g. "page", "category") */
					'warning'  => __( 'Warning: conflicts with an existing %s', 'query2slug' ),
					'checking' => __( 'Checking...', 'query2slug' ),
				),
			) );
		}
	}

	/**
	 * Register settings for the settings page.
	 */
	public function register_settings(): void {
		register_setting( 'q2s_settings', 'q2s_prefix', array(
			'type'              => 'string',
			'sanitize_callback' => function ( $value ) {
				$clean = sanitize_title( $value );
				if ( empty( $clean ) ) {
					add_settings_error( 'q2s_prefix', 'q2s_prefix_empty', __( 'Prefix cannot be empty. Reset to default.', 'query2slug' ) );
					return 'lp';
				}

				// Warn if prefix collides with existing WP content.
				$collision = self::check_prefix_collision( $clean );
				if ( $collision ) {
					add_settings_error(
						'q2s_prefix',
						'q2s_prefix_collision',
						sprintf(
							/* translators: 1: prefix, 2: type of collision (e.g. "page", "category") */
							__( 'Warning: the prefix "%1$s" conflicts with an existing %2$s. This may cause routing issues. Consider using a different prefix.', 'query2slug' ),
							$clean,
							$collision
						),
						'warning'
					);
				}

				set_transient( 'q2s_flush_rewrite', true );
				return $clean;
			},
			'default'           => 'lp',
		) );

		register_setting( 'q2s_settings', 'q2s_delete_data', array(
			'type'              => 'boolean',
			'sanitize_callback' => function ( $value ) {
				return (bool) $value;
			},
			'default'           => false,
		) );
	}

	/**
	 * Handle add/edit/delete form submissions.
	 */
	public function handle_form_submissions(): void {
		// Handle rule save.
		if ( isset( $_POST['q2s_save_rule'] ) ) {
			check_admin_referer( 'q2s_save_rule' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'query2slug' ) );
			}

			$id = isset( $_POST['q2s_rule_id'] ) ? absint( $_POST['q2s_rule_id'] ) : 0;

			$filter_keys   = isset( $_POST['q2s_filter_key'] ) && is_array( $_POST['q2s_filter_key'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['q2s_filter_key'] ) ) : array();
			$filter_values = isset( $_POST['q2s_filter_value'] ) && is_array( $_POST['q2s_filter_value'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['q2s_filter_value'] ) ) : array();

			$filters = array();
			foreach ( $filter_keys as $i => $key ) {
				$value = $filter_values[ $i ] ?? '';
				if ( '' !== $key && '' !== $value ) {
					$filters[ $key ] = $value;
				}
			}

			$result = Q2S_DB::save_rule( array(
				'id'      => $id,
				'slug'    => isset( $_POST['q2s_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['q2s_slug'] ) ) : '',
				'filters' => $filters,
				'status'  => isset( $_POST['q2s_status'] ) ? absint( $_POST['q2s_status'] ) : 1,
			) );

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'q2s_rule', 'q2s_rule', $result->get_error_message() );
				set_transient( 'settings_errors', get_settings_errors(), 30 );
				wp_safe_redirect( add_query_arg( 'settings-updated', 'false', wp_get_referer() ) );
				exit;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=q2s-rules&message=saved' ) );
			exit;
		}

		// Handle rule delete.
		if ( isset( $_GET['q2s_action'] ) && 'delete' === $_GET['q2s_action'] && isset( $_GET['rule_id'] ) ) {
			check_admin_referer( 'q2s_delete_' . absint( $_GET['rule_id'] ) );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'query2slug' ) );
			}

			Q2S_DB::delete_rule( absint( $_GET['rule_id'] ) );
			wp_safe_redirect( admin_url( 'admin.php?page=q2s-rules&message=deleted' ) );
			exit;
		}

		// Handle bulk actions (WP_List_Table uses 'action' / 'action2' field names).
		// Skip if this is an AJAX request — 'action' is used by WP AJAX for the handler name.
		if ( ! wp_doing_ajax() ) {
			$bulk_action = '';
			if ( isset( $_POST['action'] ) && '-1' !== $_POST['action'] ) {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			} elseif ( isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ) {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['action2'] ) );
			}
		} else {
			$bulk_action = '';
		}

		if ( $bulk_action && isset( $_POST['q2s_rule_ids'] ) && is_array( $_POST['q2s_rule_ids'] ) ) {
			check_admin_referer( 'bulk-rules' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'query2slug' ) );
			}

			$action = $bulk_action;
			$ids    = array_map( 'absint', $_POST['q2s_rule_ids'] );

			foreach ( $ids as $rule_id ) {
				switch ( $action ) {
					case 'activate':
						Q2S_DB::set_status( $rule_id, 1 );
						break;
					case 'deactivate':
						Q2S_DB::set_status( $rule_id, 0 );
						break;
					case 'delete':
						Q2S_DB::delete_rule( $rule_id );
						break;
				}
			}

			wp_safe_redirect( admin_url( 'admin.php?page=q2s-rules&message=bulk_done' ) );
			exit;
		}
	}

	/**
	 * Render the rules list page.
	 */
	public function render_list_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of admin notice type, no state change.
		$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

		$table = new Q2S_Rules_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Query2Slug Rules', 'query2slug' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=q2s-edit' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'query2slug' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( 'saved' === $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rule saved.', 'query2slug' ); ?></p></div>
			<?php elseif ( 'deleted' === $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rule deleted.', 'query2slug' ); ?></p></div>
			<?php elseif ( 'bulk_done' === $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Bulk action completed.', 'query2slug' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the add/edit rule page.
	 */
	public function render_edit_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only: loads rule for editing, no state change.
		$rule_id = isset( $_GET['rule_id'] ) ? absint( $_GET['rule_id'] ) : 0;
		$rule    = $rule_id ? Q2S_DB::get_rule( $rule_id ) : null;
		$prefix  = Q2S_Rewrite::get_prefix();

		$slug    = $rule ? $rule['slug'] : '';
		$filters = $rule ? $rule['filters'] : array( '' => '' );
		$status  = $rule ? $rule['status'] : 1;

		if ( empty( $filters ) ) {
			$filters = array( '' => '' );
		}
		?>
		<div class="wrap">
			<h1><?php echo $rule ? esc_html__( 'Edit Rule', 'query2slug' ) : esc_html__( 'Add New Rule', 'query2slug' ); ?></h1>

			<?php settings_errors( 'q2s_rule' ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=q2s-edit' ) ); ?>" id="q2s-rule-form">
				<?php wp_nonce_field( 'q2s_save_rule' ); ?>
				<input type="hidden" name="q2s_rule_id" value="<?php echo esc_attr( $rule_id ); ?>">

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">

						<!-- Main content -->
						<div id="post-body-content">

							<!-- Slug field (title-like) -->
							<div id="titlediv">
								<div id="titlewrap">
									<label class="screen-reader-text" for="q2s_slug"><?php esc_html_e( 'Slug', 'query2slug' ); ?></label>
									<input type="text"
										   id="q2s_slug"
										   name="q2s_slug"
										   value="<?php echo esc_attr( $slug ); ?>"
										   class="large-text"
										   required
										   pattern="[a-z0-9][a-z0-9-]*[a-z0-9]|[a-z0-9]"
										   placeholder="<?php esc_attr_e( 'Enter slug (e.g. disney-summer-sale)', 'query2slug' ); ?>"
										   autocomplete="off">
								</div>
								<div class="inside">
									<span id="q2s-slug-status"></span>
									<span id="q2s-slug-sanitized" style="display:none;"></span>
									<p id="q2s-url-preview">
										<?php echo esc_html( home_url( '/' . $prefix . '/' ) ); ?><strong id="q2s-slug-preview"><?php echo esc_html( $slug ?: '...' ); ?></strong>/
									</p>
								</div>
							</div>

							<!-- Filters metabox -->
							<div class="postbox">
								<div class="postbox-header">
									<h2><?php esc_html_e( 'Query Filters', 'query2slug' ); ?></h2>
								</div>
								<div class="inside">
									<p class="description"><?php esc_html_e( 'Define the query parameters this slug will resolve to. Start typing to see available taxonomies and terms.', 'query2slug' ); ?></p>
									<div id="q2s-filters-container">
										<?php foreach ( $filters as $key => $value ) : ?>
											<div class="q2s-filter-row">
												<input type="text"
													   name="q2s_filter_key[]"
													   value="<?php echo esc_attr( $key ); ?>"
													   placeholder="<?php esc_attr_e( 'Parameter (e.g. product_cat)', 'query2slug' ); ?>"
													   class="regular-text q2s-filter-key">
												<span class="q2s-filter-eq">=</span>
												<input type="text"
													   name="q2s_filter_value[]"
													   value="<?php echo esc_attr( $value ); ?>"
													   placeholder="<?php esc_attr_e( 'Value (e.g. t-shirt)', 'query2slug' ); ?>"
													   class="regular-text q2s-filter-value">
												<button type="button" class="button q2s-remove-filter" title="<?php esc_attr_e( 'Remove', 'query2slug' ); ?>">&minus;</button>
											</div>
										<?php endforeach; ?>
									</div>
									<p>
										<button type="button" class="button" id="q2s-add-filter">
											<?php esc_html_e( '+ Add Filter', 'query2slug' ); ?>
										</button>
									</p>
								</div>
							</div>

						</div>

						<!-- Sidebar -->
						<div id="postbox-container-1" class="postbox-container">
							<div class="postbox">
								<div class="postbox-header">
									<h2><?php esc_html_e( 'Publish', 'query2slug' ); ?></h2>
								</div>
								<div class="inside">
									<div class="misc-pub-section">
										<label for="q2s_status">
											<strong><?php esc_html_e( 'Status:', 'query2slug' ); ?></strong>
										</label>
										<select name="q2s_status" id="q2s_status">
											<option value="1" <?php selected( $status, 1 ); ?>><?php esc_html_e( 'Active', 'query2slug' ); ?></option>
											<option value="0" <?php selected( $status, 0 ); ?>><?php esc_html_e( 'Inactive', 'query2slug' ); ?></option>
										</select>
									</div>
								</div>
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<?php submit_button( $rule ? __( 'Update Rule', 'query2slug' ) : __( 'Save Rule', 'query2slug' ), 'primary', 'q2s_save_rule', false ); ?>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>

					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		$prefix      = get_option( 'q2s_prefix', 'lp' );
		$delete_data = get_option( 'q2s_delete_data', false );
		$rule_count  = count( Q2S_DB::get_rules() );
		$active      = count( Q2S_DB::get_rules( 1 ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Query2Slug Settings', 'query2slug' ); ?></h1>

			<?php settings_errors( 'q2s_prefix' ); ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">

					<!-- Main content -->
					<div id="post-body-content">
						<form method="post" action="options.php" id="q2s-settings-form">
							<?php settings_fields( 'q2s_settings' ); ?>

							<!-- URL Prefix -->
							<div class="postbox">
								<div class="postbox-header">
									<h2><?php esc_html_e( 'URL Prefix', 'query2slug' ); ?></h2>
								</div>
								<div class="inside">
									<p class="description">
										<?php esc_html_e( 'All your slugs will be served under this prefix. Choose something short and unique that does not conflict with existing pages or categories.', 'query2slug' ); ?>
									</p>
									<table class="form-table" role="presentation">
										<tr>
											<th scope="row">
												<label for="q2s_prefix"><?php esc_html_e( 'Prefix', 'query2slug' ); ?></label>
											</th>
											<td>
												<input type="text"
													   id="q2s_prefix"
													   name="q2s_prefix"
													   value="<?php echo esc_attr( $prefix ); ?>"
													   class="regular-text"
													   required>
												<span id="q2s-prefix-status" class="q2s-prefix-status"></span>
												<p class="description" id="q2s-prefix-example">
													<?php echo esc_html( home_url( '/' ) ); ?><strong id="q2s-prefix-preview"><?php echo esc_html( $prefix ); ?></strong>/your-slug/
												</p>
											</td>
										</tr>
									</table>
								</div>
							</div>

							<!-- Data Management -->
							<div class="postbox">
								<div class="postbox-header">
									<h2><?php esc_html_e( 'Data Management', 'query2slug' ); ?></h2>
								</div>
								<div class="inside">
									<table class="form-table" role="presentation">
										<tr>
											<th scope="row">
												<?php esc_html_e( 'Uninstall', 'query2slug' ); ?>
											</th>
											<td>
												<fieldset>
													<label for="q2s_delete_data">
														<input type="checkbox"
															   id="q2s_delete_data"
															   name="q2s_delete_data"
															   value="1"
															   <?php checked( $delete_data ); ?>>
														<?php esc_html_e( 'Delete all data on uninstall', 'query2slug' ); ?>
													</label>
													<p class="description">
														<?php esc_html_e( 'When enabled, all rules and settings will be permanently removed when the plugin is deleted. Leave unchecked to preserve your data if you plan to reinstall.', 'query2slug' ); ?>
													</p>
												</fieldset>
											</td>
										</tr>
									</table>
								</div>
							</div>

							<?php submit_button(); ?>
						</form>
					</div>

					<!-- Sidebar -->
					<div id="postbox-container-1" class="postbox-container">
						<div class="postbox">
							<div class="postbox-header">
								<h2><?php esc_html_e( 'Overview', 'query2slug' ); ?></h2>
							</div>
							<div class="inside">
								<ul class="q2s-overview-list">
									<li>
										<span class="dashicons dashicons-admin-links"></span>
										<?php
										printf(
											/* translators: %d: number of rules */
											esc_html( _n( '%d rule', '%d rules', $rule_count, 'query2slug' ) ),
											intval( $rule_count )
										);
										?>
									</li>
									<li>
										<span class="dashicons dashicons-yes-alt q2s-status-active"></span>
										<?php
										printf(
											/* translators: %d: number of active rules */
											esc_html__( '%d active', 'query2slug' ),
											intval( $active )
										);
										?>
									</li>
									<li>
										<span class="dashicons dashicons-marker q2s-status-inactive"></span>
										<?php
										printf(
											/* translators: %d: number of inactive rules */
											esc_html__( '%d inactive', 'query2slug' ),
											intval( $rule_count - $active )
										);
										?>
									</li>
								</ul>
								<p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=q2s-rules' ) ); ?>" class="button button-secondary" style="width:100%;text-align:center;">
										<?php esc_html_e( 'Manage Rules', 'query2slug' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Check if a prefix collides with existing WP content.
	 *
	 * @return string|null Type of collision (e.g. "page", "category"), or null if no collision.
	 */
	public static function check_prefix_collision( string $prefix ): ?string {
		// Check pages and posts.
		$post = get_page_by_path( $prefix, OBJECT, array( 'page', 'post' ) );
		if ( $post ) {
			return $post->post_type;
		}

		// Check all public taxonomies for a term with this slug at root level.
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		foreach ( $taxonomies as $tax ) {
			$term = get_term_by( 'slug', $prefix, $tax );
			if ( $term ) {
				$tax_obj = get_taxonomy( $tax );
				return $tax_obj ? $tax_obj->labels->singular_name : $tax;
			}
		}

		// Check WooCommerce endpoints if WC is active.
		if ( function_exists( 'wc_get_page_id' ) ) {
			$wc_pages = array( 'shop', 'cart', 'checkout', 'myaccount' );
			foreach ( $wc_pages as $wc_page ) {
				$page_id = wc_get_page_id( $wc_page );
				if ( $page_id > 0 ) {
					$page = get_post( $page_id );
					if ( $page && $page->post_name === $prefix ) {
						return sprintf(
							/* translators: %s: WooCommerce page type */
							__( 'WooCommerce %s page', 'query2slug' ),
							$wc_page
						);
					}
				}
			}
		}

		// Check existing rewrite rules for any pattern starting with this prefix.
		global $wp_rewrite;
		$rules = $wp_rewrite->wp_rewrite_rules();
		if ( ! is_array( $rules ) ) {
			return null;
		}
		foreach ( $rules as $pattern => $query ) {
			if ( str_starts_with( $pattern, $prefix . '/' ) || str_starts_with( $pattern, $prefix . '(' ) ) {
				return __( 'rewrite rule', 'query2slug' );
			}
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * Check if a slug is available.
	 */
	public function ajax_check_slug(): void {
		check_ajax_referer( 'q2s_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$slug    = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : 0;

		if ( empty( $slug ) ) {
			wp_send_json_error( __( 'Slug cannot be empty.', 'query2slug' ) );
		}

		$existing = Q2S_DB::get_rule_by_slug( $slug );

		if ( $existing && $existing['id'] !== $rule_id ) {
			wp_send_json_error( __( 'This slug is already in use.', 'query2slug' ) );
		}

		wp_send_json_success( __( 'Available', 'query2slug' ) );
	}

	/**
	 * Toggle a rule's active/inactive status.
	 */
	public function ajax_toggle_status(): void {
		check_ajax_referer( 'q2s_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : 0;

		if ( ! $rule_id ) {
			wp_send_json_error( __( 'Invalid rule ID.', 'query2slug' ) );
		}

		Q2S_DB::toggle_status( $rule_id );

		$rule = Q2S_DB::get_rule( $rule_id );
		wp_send_json_success( array( 'status' => $rule ? $rule['status'] : 0 ) );
	}

	/**
	 * Get registered taxonomies for autocomplete.
	 */
	public function ajax_get_taxonomies(): void {
		check_ajax_referer( 'q2s_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$search     = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$results    = array();

		foreach ( $taxonomies as $tax ) {
			$query_var = $tax->query_var ?: $tax->name;
			$label     = $tax->labels->singular_name . ' (' . $query_var . ')';

			if ( '' === $search || false !== stripos( $label, $search ) || false !== stripos( $query_var, $search ) ) {
				$results[] = array(
					'label' => $label,
					'value' => $query_var,
				);
			}
		}

		wp_send_json( $results );
	}

	/**
	 * Get terms for a given taxonomy (for value autocomplete).
	 */
	public function ajax_get_terms(): void {
		check_ajax_referer( 'q2s_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
		$search   = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json( array() );
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'search'     => $search,
			'hide_empty' => false,
			'number'     => 20,
		) );

		if ( is_wp_error( $terms ) ) {
			wp_send_json( array() );
		}

		$results = array();
		foreach ( $terms as $term ) {
			$results[] = array(
				'label' => $term->name . ' (' . $term->slug . ')',
				'value' => $term->slug,
			);
		}

		wp_send_json( $results );
	}

	/**
	 * Check if a prefix collides with existing WP content (real-time).
	 */
	public function ajax_check_prefix(): void {
		check_ajax_referer( 'q2s_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$prefix = isset( $_POST['prefix'] ) ? sanitize_title( wp_unslash( $_POST['prefix'] ) ) : '';

		if ( empty( $prefix ) ) {
			wp_send_json_error( __( 'Prefix cannot be empty.', 'query2slug' ) );
		}

		$collision = self::check_prefix_collision( $prefix );

		if ( $collision ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: type of collision */
					__( 'Warning: conflicts with an existing %s', 'query2slug' ),
					$collision
				)
			);
		}

		wp_send_json_success();
	}
}
