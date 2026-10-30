<?php
/**
 * Activity logs list table (Free).
 *
 * @package Password_Protected
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Password_Protected_Activity_Logs_List_Table' ) ) {

	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

	/**
	 * WP_List_Table for site-wide password attempt logs.
	 */
	class Password_Protected_Activity_Logs_List_Table extends WP_List_Table {

		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct(
				array(
					'plural'   => 'activity_logs',
					'singular' => 'activity_log',
					'ajax'     => false,
				)
			);

			$this->handle_delete_activity_log();
		}

		/**
		 * Prepare table items.
		 */
		public function prepare_items() {
			$activity_logs = $this->get_activity_logs();
			$per_page      = $this->get_items_per_page( 'password_protected_activity_logs_per_page', 10 );
			$current_page  = $this->get_pagenum();
			$total_items   = count( $activity_logs );

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
				)
			);

			$this->items = array_slice( $activity_logs, ( ( $current_page - 1 ) * $per_page ), $per_page );

			$this->_column_headers = array( $this->get_columns(), array(), array() );
		}

		/**
		 * Table columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'cb'            => '<input type="checkbox" />',
				'ip'            => __( 'IP', 'password-protected' ),
				'browser'       => __( 'Browser', 'password-protected' ),
				'status'        => __( 'Status', 'password-protected' ),
				'used_password' => __( 'Password', 'password-protected' ),
				'object_type'   => __( 'Context', 'password-protected' ),
				'created_at'    => __( 'Date Time', 'password-protected' ),
			);
		}

		/**
		 * Checkbox column.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="activity_logs[]" value="%s" />',
				esc_attr( $item['id'] )
			);
		}

		/**
		 * Default column output.
		 *
		 * @param array  $item         Row.
		 * @param string $column_name Column.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'ip':
				case 'browser':
				case 'status':
					return esc_html( $item[ $column_name ] );
					case 'object_type':
					$type = isset( $item['object_type'] ) ? $item['object_type'] : '';
					return esc_html( $type ? $type : 'global' );
				case 'created_at':
					$ts = isset( $item['created_at'] ) ? (int) $item['created_at'] : 0;
					return $ts ? esc_html( gmdate( 'F j, Y, g:i a', $ts ) ) : '';
				default:
					return '';
			}
		}

		/**
		 * Password column (masked by default; reveal via admin toggle).
		 *
		 * @param array $item Row.
		 * @return string
		 */
		public function column_used_password( $item ) {
			$password = isset( $item['used_password'] ) ? (string) $item['used_password'] : '';

			if ( '' === $password ) {
				return '&mdash;';
			}

			return sprintf(
				'<span class="pp-activity-log-password"><span class="pp-activity-log-password-mask" aria-hidden="true">********</span><span class="pp-activity-log-password-value" hidden>%1$s</span> <button type="button" class="button-link pp-activity-log-password-toggle" aria-expanded="false" aria-label="%2$s"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button></span>',
				esc_html( $password ),
				esc_attr__( 'Show password', 'password-protected' )
			);
		}

		/**
		 * IP column with row actions.
		 *
		 * @param array $item Row.
		 * @return string
		 */
		public function column_ip( $item ) {
			$nonce = wp_create_nonce( 'activity-log-row-action' );
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin list row links; delete verifies its own nonce.
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'password-protected';
			$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'logs';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$delete_link = add_query_arg(
				array(
					'page'     => $page,
					'tab'      => $tab,
					'sub-tab'  => 'activity_logs',
					'action'   => 'delete',
					'log_id'   => $item['id'],
					'_wpnonce' => $nonce,
				),
				admin_url( 'admin.php' )
			);

			$actions = array(
				'delete' => '<a href="' . esc_url( $delete_link ) . '" class="pp_confirmation">' . esc_html__( 'Delete', 'password-protected' ) . '</a>',
			);

			return sprintf( '%1$s %2$s', esc_html( $item['ip'] ), $this->row_actions( $actions ) );
		}

		/**
		 * Render the full table UI.
		 */
		public static function display_table() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$table = new self();
			$table->prepare_items();
			$table->render_search_form();
			$table->filter_logs();
			echo '<form method="post">';
			wp_nonce_field( 'password_protected_activity_logs_table', 'activity_logs_table_nonce' );
			$table->display();
			echo '</form>';
		}

		/**
		 * Search form above the table.
		 */
		public function render_search_form() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Preserve active date filter in search form.
			$action_args = array(
				'page'    => 'password-protected',
				'tab'     => 'logs',
				'sub-tab' => 'activity_logs',
			);
			if ( ! empty( $_GET['show_logs'] ) ) {
				$action_args['show_logs'] = sanitize_text_field( wp_unslash( $_GET['show_logs'] ) );
				if ( ! empty( $_GET['_wpnonce'] ) ) {
					$action_args['_wpnonce'] = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$form_action = add_query_arg( $action_args, admin_url( 'admin.php' ) );
			echo '<form method="post" name="pp_search_activity_log_form" action="' . esc_url( $form_action ) . '">';
			wp_nonce_field( 'password_protected_search_activity_logs', 'search_activity_logs_nonce' );
			$this->search_box( __( 'Search', 'password-protected' ), 'search_pp_activity_log' );
			echo '</form>';
		}

		/**
		 * Bulk actions.
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			return array(
				'delete'     => __( 'Delete', 'password-protected' ),
				'all_delete' => __( 'Delete all logs', 'password-protected' ),
			);
		}

		/**
		 * Date filter links.
		 */
		public function filter_logs() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filter links include activity-logs-filter nonce.
			$current = ( isset( $_GET['show_logs'] ) && sanitize_text_field( wp_unslash( $_GET['show_logs'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['show_logs'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$nonce = wp_create_nonce( 'activity-logs-filter' );
			$url   = admin_url( 'admin.php?page=password-protected&tab=logs&sub-tab=activity_logs&_wpnonce=' . $nonce );
			?>
			<ul class="subsubsub">
				<li>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo ( '' === $current ) ? 'current' : ''; ?>"><?php esc_html_e( 'All', 'password-protected' ); ?></a> |
				</li>
				<li>
					<a href="<?php echo esc_url( $url . '&show_logs=today' ); ?>" class="<?php echo ( 'today' === $current ) ? 'current' : ''; ?>"><?php esc_html_e( 'Today', 'password-protected' ); ?></a> |
				</li>
				<li>
					<a href="<?php echo esc_url( $url . '&show_logs=yesterday' ); ?>" class="<?php echo ( 'yesterday' === $current ) ? 'current' : ''; ?>"><?php esc_html_e( 'Yesterday', 'password-protected' ); ?></a> |
				</li>
				<li>
					<a href="<?php echo esc_url( $url . '&show_logs=thisweek' ); ?>" class="<?php echo ( 'thisweek' === $current ) ? 'current' : ''; ?>"><?php esc_html_e( 'This Week', 'password-protected' ); ?></a> |
				</li>
				<li>
					<a href="<?php echo esc_url( $url . '&show_logs=thismonth' ); ?>" class="<?php echo ( 'thismonth' === $current ) ? 'current' : ''; ?>"><?php esc_html_e( 'This Month', 'password-protected' ); ?></a>
				</li>
			</ul>
			<?php
		}

		/**
		 * Empty state message.
		 */
		public function no_items() {
			esc_html_e( 'No activity logs found.', 'password-protected' );
		}

		/**
		 * Register per-page screen option.
		 */
		public static function register_screen_option() {
			add_screen_option(
				'per_page',
				array(
					'option'  => 'password_protected_activity_logs_per_page',
					'label'   => __( 'Activity Logs Per Page', 'password-protected' ),
					'default' => 10,
				)
			);
		}

		/**
		 * Fetch log rows.
		 *
		 * @return array
		 */
		private function get_activity_logs() {
			return Password_Protected_Activity_Logs::get_items();
		}

		/**
		 * Process delete requests.
		 */
		private function handle_delete_activity_log() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->bulk_delete();
			$this->truncate_table();
			$this->row_delete();
		}

		/**
		 * Bulk delete selected rows.
		 */
		private function bulk_delete() {
			if ( ! isset( $_POST['activity_logs'], $_POST['action'], $_POST['action2'] ) ) {
				return;
			}

			if ( ! isset( $_POST['activity_logs_table_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['activity_logs_table_nonce'] ) ), 'password_protected_activity_logs_table' ) ) {
				wp_die( esc_html__( 'Sorry, your nonce did not verify.', 'password-protected' ) );
			}

			$logs    = array_map( 'absint', (array) wp_unslash( $_POST['activity_logs'] ) );
			$ids     = implode( ',', $logs );
			$action  = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			$action2 = sanitize_text_field( wp_unslash( $_POST['action2'] ) );

			if ( 'delete' === $action && $action === $action2 ) {
				Password_Protected_Activity_Logs::delete_items( $ids );
			}
		}

		/**
		 * Delete all log rows.
		 */
		private function truncate_table() {
			if ( ! isset( $_POST['action'], $_POST['action2'] ) ) {
				return;
			}

			if ( 'all_delete' !== sanitize_text_field( wp_unslash( $_POST['action'] ) ) || 'all_delete' !== sanitize_text_field( wp_unslash( $_POST['action2'] ) ) ) {
				return;
			}

			if ( ! isset( $_POST['activity_logs_table_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['activity_logs_table_nonce'] ) ), 'password_protected_activity_logs_table' ) ) {
				wp_die( esc_html__( 'Sorry, your nonce did not verify.', 'password-protected' ) );
			}

			Password_Protected_Activity_Logs::delete_all_items();
		}

		/**
		 * Single row delete via GET.
		 */
		private function row_delete() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified below.
			if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['action'], $_GET['log_id'] ) ) {
				return;
			}

			$page   = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			$tab    = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
			$log_id = absint( wp_unslash( $_GET['log_id'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( 'password-protected' !== $page || 'logs' !== $tab || 'delete' !== $action || ! $log_id ) {
				return;
			}

			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'activity-log-row-action' ) ) {
				wp_die( esc_html__( 'Security check: Your nonce did not verify!', 'password-protected' ) );
			}

			Password_Protected_Activity_Logs::delete_item( $log_id );
		}
	}
}
