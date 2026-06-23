<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IMF_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . Imedia_Forms::CPT, array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'redirect_post_location', array( $this, 'redirect_after_save' ), 10, 2 );
	}

	/* ========================================
		ADMIN MENU — WITH SUBMENUS
		======================================== */
	public function register_admin_menu() {
		add_menu_page(
			'IMedia Registration',
			'IMedia Registration',
			'manage_options',
			'imedia-forms',
			array( $this, 'render_dashboard' ),
			'dashicons-feedback',
			30
		);

		add_submenu_page(
			'imedia-forms',
			'All Forms',
			'Forms',
			'manage_options',
			'imedia-forms',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'imedia-forms',
			'New Form',
			'New Form',
			'manage_options',
			'imedia-forms-new',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'imedia-forms',
			'Entries',
			'Entries',
			'manage_options',
			'imedia-forms-all-entries',
			array( $this, 'render_all_entries_page' )
		);

		add_submenu_page(
			'imedia-forms',
			'Settings',
			'Settings',
			'manage_options',
			'imedia-forms-settings',
			array( $this, 'render_settings_page' )
		);

		// Hidden pages (not in sidebar)
		add_submenu_page(
			null,
			'Form Entries',
			'Entries',
			'manage_options',
			'imedia-forms-entries',
			array( $this, 'render_entries_page' )
		);

		add_submenu_page(
			null,
			'Entry Detail',
			'Entry Detail',
			'manage_options',
			'imedia-forms-entry-detail',
			array( $this, 'render_entry_detail_page' )
		);
	}

	/* ========================================
		ENQUEUE ADMIN ASSETS
		======================================== */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();

		// Dashboard pages (all forms + new form) & Settings page
		$current_page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$dashboard_pages = array(
			'imedia-forms',
			'imedia-forms-new',
			'imedia-forms-settings',
			'imedia-forms-entries',
			'imedia-forms-all-entries',
			'imedia-forms-entry-detail',
		);

		if ( in_array( $current_page, $dashboard_pages ) ) {
			wp_enqueue_style( 'imf-admin-css', plugin_dir_url( __DIR__ ) . 'resources/assets/css/form-builder.css', array(), Imedia_Forms::VERSION );
			wp_enqueue_script( 'imf-dashboard-js', plugin_dir_url( __DIR__ ) . 'resources/assets/js/dashboard.js', array(), Imedia_Forms::VERSION, true );
			wp_localize_script(
				'imf-dashboard-js',
				'imfDashboard',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'imf_nonce' ),
					'edit_base_url' => admin_url( 'post.php' ),
					// Auto-open the modal when user lands from 'New Form' sidebar link
					'autoOpen'      => ( $current_page === 'imedia-forms-new' ) ? '1' : '0',
				)
			);

			// Entries pages — extra assets
			if ( in_array( $current_page, array( 'imedia-forms-entries', 'imedia-forms-all-entries', 'imedia-forms-entry-detail' ) ) ) {
				wp_enqueue_style( 'imf-entries-css', plugin_dir_url( __DIR__ ) . 'resources/assets/css/entries.css', array( 'imf-admin-css' ), Imedia_Forms::VERSION );
				wp_enqueue_script( 'imf-entries-js', plugin_dir_url( __DIR__ ) . 'resources/assets/js/entries.js', array( 'imf-dashboard-js' ), Imedia_Forms::VERSION, true );
			}
		}

		// Builder page
		if ( $screen && $screen->post_type === Imedia_Forms::CPT && in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			wp_enqueue_style( 'imf-builder-css', plugin_dir_url( __DIR__ ) . 'resources/assets/css/form-builder.css', array(), Imedia_Forms::VERSION );
			wp_enqueue_script( 'sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '1.15.0', true );
			wp_enqueue_script( 'imf-builder-js', plugin_dir_url( __DIR__ ) . 'resources/assets/js/form-builder.js', array( 'sortablejs' ), Imedia_Forms::VERSION, true );

			add_action(
				'admin_head',
				function () {
					echo '<style>
                    #wpfooter, #screen-meta, #screen-meta-links { display:none!important; }
                    #poststuff #post-body.columns-2 { margin-right: 0; }
                    #postbox-container-1 { display: none; }
                    #post-body-content { margin-bottom: 0; }
                    .wrap > h1, .wrap > .wp-header-end { display: none !important; }
                    #titlediv { display: none !important; }
                    #minor-publishing-actions, #misc-publishing-actions, #major-publishing-actions { display: none !important; }
                    #submitdiv { display: none !important; }
                </style>';
				}
			);
		}

		// Highlight the correct submenu
		if ( $screen && $screen->post_type === Imedia_Forms::CPT ) {
			add_action(
				'admin_head',
				function () {
					echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var menu = document.querySelector("#toplevel_page_imedia-forms");
                        if (menu) {
                            menu.classList.add("wp-has-current-submenu", "wp-menu-open");
                            menu.classList.remove("wp-not-current-submenu");
                        }
                    });
                </script>';
				}
			);
		}
	}

	/* ========================================
		DASHBOARD — FORM LIST
		======================================== */
	public function render_dashboard() {
		global $wpdb;
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
		$search        = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		$args = array(
			'post_type'      => Imedia_Forms::CPT,
			'posts_per_page' => 50,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $search ) {
			$args['s'] = $search;
		}

		$forms = get_posts( $args );

		$count_all      = count( $forms );
		$count_active   = 0;
		$count_inactive = 0;
		foreach ( $forms as $form ) {
			$st = get_post_meta( $form->ID, Imedia_Forms::META_STATUS, true );
			if ( $st === 'inactive' ) {
				++$count_inactive;
			} else {
				++$count_active;
			}
		}

		if ( $status_filter === 'active' ) {
			$forms = array_filter(
				$forms,
				function ( $f ) {
					return get_post_meta( $f->ID, Imedia_Forms::META_STATUS, true ) !== 'inactive';
				}
			);
		} elseif ( $status_filter === 'inactive' ) {
			$forms = array_filter(
				$forms,
				function ( $f ) {
					return get_post_meta( $f->ID, Imedia_Forms::META_STATUS, true ) === 'inactive';
				}
			);
		}

		$add_new_url = admin_url( 'post-new.php?post_type=' . Imedia_Forms::CPT );
		$base_url    = admin_url( 'admin.php?page=imedia-forms' );
		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/dashboard.php';
	}

	/* ========================================
		SETTINGS PAGE
		======================================== */
	public function render_settings_page() {
		// Save settings
		if ( isset( $_POST['imf_settings_nonce'] ) && wp_verify_nonce( $_POST['imf_settings_nonce'], 'imf_save_settings' ) ) {
			update_option( 'imf_default_submit_text', sanitize_text_field( $_POST['imf_default_submit_text'] ?? 'Submit' ) );
			update_option( 'imf_recaptcha_site_key', sanitize_text_field( $_POST['imf_recaptcha_site_key'] ?? '' ) );
			update_option( 'imf_recaptcha_secret_key', sanitize_text_field( $_POST['imf_recaptcha_secret_key'] ?? '' ) );

			// Phase 7: standalone app URL + shared secret.
			// imf_app_url — validated as an http(s) URL; empty allowed
			// (the REST handler falls back to site_url() + '/imedia-registration').
			// imf_shared_secret — write-only; the form field is type="password"
			// and we never echo the stored value. Empty input = keep the stored value.
			$raw_url    = wp_unslash( $_POST['imf_app_url'] ?? '' );
			$raw_secret = wp_unslash( $_POST['imf_shared_secret'] ?? '' );

			$app_url = is_string( $raw_url ) ? trim( $raw_url ) : '';
			if ( $app_url === '' ) {
				update_option( 'imf_app_url', '' );
			} else {
				$cleaned = esc_url_raw( $app_url );
				// Reject anything that isn't a real http(s) URL. wp_validate_url
				// returns false for malformed input.
				$validated = $cleaned !== '' ? wp_validate_url( $cleaned, '' ) : '';
				if ( $validated === '' || ! preg_match( '#^https?://#i', $validated ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>Registration App URL is not a valid http(s) URL. Saved as empty.</p></div>';
					update_option( 'imf_app_url', '' );
				} else {
					update_option( 'imf_app_url', $validated );
				}
			}

			if ( is_string( $raw_secret ) && $raw_secret !== '' ) {
				// Store as-is. WP options are not encrypted at rest; we
				// document this in the README. The secret is masked in
				// the form and never echoed back.
				update_option( 'imf_shared_secret', sanitize_text_field( $raw_secret ) );
			}
			// Empty $raw_secret means "keep current value" — do nothing.

			echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
		}

		$default_text = get_option( 'imf_default_submit_text', 'Submit' );
		$site_key     = get_option( 'imf_recaptcha_site_key', '' );
		$secret_key   = get_option( 'imf_recaptcha_secret_key', '' );
		$app_url      = get_option( 'imf_app_url', '' );
		// imf_shared_secret is read once but never echoed into HTML.
		$app_secret_present = get_option( 'imf_shared_secret', '' ) !== '';
		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/settings.php';
	}

	/* ========================================
		META BOXES (FORM BUILDER)
		======================================== */
	public function register_meta_boxes() {
		remove_meta_box( 'submitdiv', Imedia_Forms::CPT, 'side' );

		add_meta_box(
			'imf_form_builder',
			'Form Builder',
			array( $this, 'render_builder' ),
			Imedia_Forms::CPT,
			'normal',
			'high'
		);
	}

	public function render_builder( $post ) {
		wp_nonce_field( 'imf_save_meta', 'imf_meta_nonce' );

		$fields_json = get_post_meta( $post->ID, Imedia_Forms::META_FIELDS, true ) ?: '[]';
		$api_url     = get_post_meta( $post->ID, Imedia_Forms::META_API_URL, true ) ?: '';
		$api_enabled = get_post_meta( $post->ID, Imedia_Forms::META_API_ENABLED, true );
		if ( $api_enabled === '' ) {
			$api_enabled = '0';
		}
		$form_title = $post->post_title ?: '';
		$shortcode  = '[imedia_form id="' . $post->ID . '"]';

		$appearance     = imf_get_appearance_defaults( $post->ID );
		$email_settings = imf_get_email_settings_defaults( $post->ID, $form_title );

		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/builder.php';
	}

	/* ========================================
		SAVE POST META
		======================================== */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['imf_meta_nonce'] ) || ! wp_verify_nonce( $_POST['imf_meta_nonce'], 'imf_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['imf_form_data'] ) ) {
			update_post_meta( $post_id, Imedia_Forms::META_FIELDS, $_POST['imf_form_data'] );
		}

		if ( isset( $_POST['imf_api_endpoint'] ) ) {
			update_post_meta( $post_id, Imedia_Forms::META_API_URL, sanitize_url( wp_unslash( $_POST['imf_api_endpoint'] ) ) );
		}

		if ( isset( $_POST['imf_api_enabled'] ) ) {
			update_post_meta( $post_id, Imedia_Forms::META_API_ENABLED, sanitize_text_field( $_POST['imf_api_enabled'] ) );
		}

		if ( isset( $_POST['imf_appearance'] ) ) {
			$appearance_raw = wp_unslash( $_POST['imf_appearance'] );
			$appearance     = json_decode( $appearance_raw, true );
			if ( is_array( $appearance ) ) {
				$safe = array(
					'title_color'       => sanitize_hex_color( $appearance['title_color'] ?? '#1e293b' ) ?: '#1e293b',
					'submit_bg_color'   => sanitize_hex_color( $appearance['submit_bg_color'] ?? '#3b82f6' ) ?: '#3b82f6',
					'submit_text_color' => sanitize_hex_color( $appearance['submit_text_color'] ?? '#ffffff' ) ?: '#ffffff',
					'submit_width'      => in_array( $appearance['submit_width'] ?? '', array( 'auto', 'full' ) ) ? $appearance['submit_width'] : 'auto',
					'submit_alignment'  => in_array( $appearance['submit_alignment'] ?? '', array( 'left', 'center', 'right' ) ) ? $appearance['submit_alignment'] : 'left',
					'enable_recaptcha'  => ! empty( $appearance['enable_recaptcha'] ) && $appearance['enable_recaptcha'] === '1' ? '1' : '0',
					'enable_honeypot'   => ! empty( $appearance['enable_honeypot'] ) && $appearance['enable_honeypot'] === '1' ? '1' : '0',
				);
				update_post_meta( $post_id, Imedia_Forms::META_APPEARANCE, $safe );
			}
		}

		if ( isset( $_POST['imf_email_settings'] ) ) {
			$email_settings_raw = wp_unslash( $_POST['imf_email_settings'] );
			$email_settings     = json_decode( $email_settings_raw, true );
			if ( is_array( $email_settings ) ) {
				$safe_email = array(
					'admin_notify_enable'   => ! empty( $email_settings['admin_notify_enable'] ) && $email_settings['admin_notify_enable'] === '1' ? '1' : '0',
					'admin_notify_to'       => sanitize_text_field( $email_settings['admin_notify_to'] ?? '' ),
					'admin_notify_reply_to' => sanitize_text_field( $email_settings['admin_notify_reply_to'] ?? '' ),
					'admin_notify_subject'  => sanitize_text_field( $email_settings['admin_notify_subject'] ?? '' ),
					'admin_notify_body'     => wp_kses_post( $email_settings['admin_notify_body'] ?? '' ),
					'user_confirm_enable'   => ! empty( $email_settings['user_confirm_enable'] ) && $email_settings['user_confirm_enable'] === '1' ? '1' : '0',
					'user_confirm_subject'  => sanitize_text_field( $email_settings['user_confirm_subject'] ?? '' ),
					'user_confirm_body'     => wp_kses_post( $email_settings['user_confirm_body'] ?? '' ),
				);
				update_post_meta( $post_id, Imedia_Forms::META_EMAIL_SETTINGS, $safe_email );
			}
		}

		$status = get_post_meta( $post_id, Imedia_Forms::META_STATUS, true );
		if ( ! $status ) {
			update_post_meta( $post_id, Imedia_Forms::META_STATUS, 'active' );
		}
	}

	/* ========================================
		REDIRECT AFTER SAVE — Stay on builder
		======================================== */
	public function redirect_after_save( $location, $post_id ) {
		$post = get_post( $post_id );
		if ( $post && $post->post_type === Imedia_Forms::CPT ) {
			return admin_url( 'post.php?post=' . $post_id . '&action=edit&imf_saved=1' );
		}
		return $location;
	}

	/* ========================================
		ENTRIES LIST PAGE
		======================================== */
	public function render_entries_page() {
		global $wpdb;
		$form_id = intval( $_GET['form_id'] ?? 0 );
		if ( ! $form_id ) {
			echo '<div class="wrap"><h1>Invalid form.</h1></div>';
			return;
		}

		$form = get_post( $form_id );
		if ( ! $form || $form->post_type !== Imedia_Forms::CPT ) {
			echo '<div class="wrap"><h1>Form not found.</h1></div>';
			return;
		}

		$table = IMF_Database::table_name();

		$status_filter = sanitize_text_field( $_GET['entry_status'] ?? 'all' );
		$search        = sanitize_text_field( $_GET['s'] ?? '' );
		$paged         = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page      = 20;
		$offset        = ( $paged - 1 ) * $per_page;

		$where = $wpdb->prepare( 'WHERE form_id = %d', $form_id );
		if ( $status_filter === 'starred' ) {
			$where .= " AND is_starred = 1 AND status = 'active'";
		} elseif ( $status_filter === 'unread' ) {
			$where .= " AND is_read = 0 AND status = 'active'";
		} elseif ( $status_filter === 'trash' ) {
			$where .= " AND status = 'trash'";
		} else {
			$where .= " AND status = 'active'";
		}

		if ( $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare( ' AND fields_data LIKE %s', $like );
		}

		$total       = $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
		$entries     = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset" );
		$total_pages = ceil( $total / $per_page );

		$count_all     = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE form_id = %d AND status = 'active'", $form_id ) );
		$count_unread  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE form_id = %d AND status = 'active' AND is_read = 0", $form_id ) );
		$count_starred = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE form_id = %d AND is_starred = 1 AND status = 'active'", $form_id ) );
		$count_trash   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE form_id = %d AND status = 'trash'", $form_id ) );

		$fields     = json_decode( get_post_meta( $form_id, Imedia_Forms::META_FIELDS, true ), true ) ?: array();
		$all_fields = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f['type'], array( 'section', 'hidden' ) ) ) {
				continue;
			}
			$all_fields[] = $f;
		}

		$pref_key        = '_imf_columns_' . $form_id;
		$user_prefs      = get_user_meta( get_current_user_id(), $pref_key, true );
		$visible_columns = is_array( $user_prefs ) ? $user_prefs : array();

		if ( empty( $user_prefs ) && ! empty( $all_fields ) ) {
			$visible_columns = array_slice(
				array_map(
					function ( $f ) {
						return $f['name'] ?? $f['id'];
					},
					$all_fields
				),
				0,
				4
			);
		}

		$forms = get_posts(
			array(
				'post_type'      => Imedia_Forms::CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$base_url      = admin_url( 'admin.php?page=imedia-forms-entries&form_id=' . $form_id );
		$dashboard_url = admin_url( 'admin.php?page=imedia-forms' );

		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/entries-list.php';
	}

	/* ========================================
		ENTRY DETAIL PAGE
		======================================== */
	public function render_entry_detail_page() {
		global $wpdb;
		$form_id  = intval( $_GET['form_id'] ?? 0 );
		$entry_id = intval( $_GET['entry_id'] ?? 0 );
		if ( ! $form_id || ! $entry_id ) {
			echo '<div class="wrap"><h1>Invalid entry.</h1></div>';
			return;
		}

		$table = IMF_Database::table_name();
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d AND form_id = %d", $entry_id, $form_id ) );
		if ( ! $entry ) {
			echo '<div class="wrap"><h1>Entry not found.</h1></div>';
			return;
		}

		if ( ! $entry->is_read ) {
			$wpdb->update( $table, array( 'is_read' => 1 ), array( 'id' => $entry_id ) );
		}

		$form        = get_post( $form_id );
		$data        = imf_normalize_entry_data( json_decode( $entry->fields_data, true ) );
		$entries_url = admin_url( 'admin.php?page=imedia-forms-entries&form_id=' . $form_id );

		$prev_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE form_id = %d AND status = 'active' AND id < %d ORDER BY id DESC LIMIT 1", $form_id, $entry_id ) );
		$next_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE form_id = %d AND status = 'active' AND id > %d ORDER BY id ASC LIMIT 1", $form_id, $entry_id ) );

		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/entry-detail.php';
	}

	/* ========================================
		ALL ENTRIES PAGE
		======================================== */
	public function render_all_entries_page() {
		global $wpdb;
		$table = IMF_Database::table_name();

		$search   = sanitize_text_field( $_GET['s'] ?? '' );
		$paged    = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$per_page = 20;
		$offset   = ( $paged - 1 ) * $per_page;

		$where = "WHERE status = 'active'";
		if ( $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare( ' AND fields_data LIKE %s', $like );
		}

		$total       = $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
		$entries     = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset" );
		$total_pages = ceil( $total / $per_page );

		$forms = get_posts(
			array(
				'post_type'      => Imedia_Forms::CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$form_map = array();
		foreach ( $forms as $f ) {
			$form_map[ $f->ID ] = $f->post_title;
		}

		$base_url      = admin_url( 'admin.php?page=imedia-forms-all-entries' );
		$dashboard_url = admin_url( 'admin.php?page=imedia-forms' );

		include plugin_dir_path( __DIR__ ) . 'resources/views/wordpress/all-entries.php';
	}
}
