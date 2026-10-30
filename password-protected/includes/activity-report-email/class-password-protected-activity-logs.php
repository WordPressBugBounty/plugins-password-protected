<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Password_Protected_Activity_Logs' ) ) {
	class Password_Protected_Activity_Logs {
		/**
		 * table
		 *
		 * @var mixed
		 */
		private static $table;
		/**
		 * now
		 *
		 * @var int
		 */
		public static $now = 0;

		/**
		 * Email report window timestamps for the current request [ start, end ].
		 *
		 * @var array|null
		 */
		private static $email_report_window = null;

		/**
		 * Method __construct
		 */
		public function __construct() {
			self::$now          = current_time( 'timestamp' );
			self::$table        = 'pp_activity_logs';
		}

		/**
		 * Method get_items
		 *
		 * @return array|object|stdClass[]|null
		 */
		public static function get_items() {

			global $wpdb;
			$table_name  = $wpdb->prefix . self::$table;
			$search_term = self::check_for_search();
			$filter      = self::check_for_filter();
			$timestamp   = self::get_time_from_keyword( $filter );
			$has_filter  = ( null !== $filter && ! empty( $timestamp ) );
			$has_search  = ( null !== $search_term );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			if ( $has_search && $has_filter ) {
				$like = '%' . $wpdb->esc_like( $search_term ) . '%';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$logs = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . $table_name . '` WHERE CONCAT_WS( \' \', `ip`, `browser`, `status` ) LIKE %s AND `created_at` BETWEEN %d AND %d ORDER BY `id` DESC',
						$like,
						(int) reset( $timestamp ),
						(int) end( $timestamp )
					),
					ARRAY_A
				);
			} elseif ( $has_search ) {
				$like = '%' . $wpdb->esc_like( $search_term ) . '%';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$logs = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . $table_name . '` WHERE CONCAT_WS( \' \', `ip`, `browser`, `status` ) LIKE %s ORDER BY `id` DESC',
						$like
					),
					ARRAY_A
				);
			} elseif ( $has_filter ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$logs = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . $table_name . '` WHERE `created_at` BETWEEN %d AND %d ORDER BY `id` DESC',
						(int) reset( $timestamp ),
						(int) end( $timestamp )
					),
					ARRAY_A
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$logs = $wpdb->get_results(
					'SELECT * FROM `' . $table_name . '` ORDER BY `id` DESC',
					ARRAY_A
				);
			}
			// phpcs:enable

			if ( ! is_wp_error( $logs ) && count( $logs ) > 0 ) {
				return $logs;
			}

			return array();
		}

		/**
		 * Method add_item
		 *
		 * @param $request $request
		 *
		 * @return bool|int|mysqli_result|null
		 */
		public static function add_item( $request ) {
			global $wpdb;
			$table_name = $wpdb->prefix . self::$table;

		$data = array(
			'ip'            => $request['ip'],
			'browser'       => $request['browser'],
			'status'        => $request['status'],
			'created_at'    => $request['created_at'],
			'used_password' => $request['used_password'],
			'password_id'   => $request['password_id'],
			'object_type'   => $request['object_type'],
		);
		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom activity log table.
			return $wpdb->insert( $table_name, $data, $format );
		}

		/**
		 * delete_item
		 *
		 * @param  mixed $id
		 *
		 * @return bool|int|mysqli_result|null
		 */
		public static function delete_item( $id ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom activity log table.
			return $wpdb->delete(
				$wpdb->prefix . self::$table,
				['id' => $id],
				['%d']
			);
		}

		/**
		 * delete_items
		 *
		 * @param  mixed $ids
		 */
		public static function delete_items( $ids ) {
			if ( is_string( $ids ) ) {
				$ids = explode( ',', $ids );
			}
			$ids = array_filter( array_map( 'absint', (array) $ids ) );
			foreach ( $ids as $id ) {
				self::delete_item( $id );
			}
		}

		/**
		 * delete_all_items
		 */
		public static function delete_all_items() {
			global $wpdb;
			$table_name = $wpdb->prefix . self::$table;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom activity log table uses $wpdb->prefix.
			$wpdb->query( "TRUNCATE TABLE `{$table_name}`" );
		}

		/**
		 * check_for_search
		 *
		 * @return string|null
		 */
		public static function check_for_search() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return null;
			}

			if( isset( $_POST['s'] ) ) {
				if ( ! isset( $_POST['search_activity_logs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['search_activity_logs_nonce'] ) ), 'password_protected_search_activity_logs' ) ) {
					wp_die('Sorry, your nonce did not verify.');
				}
				return trim( sanitize_text_field( wp_unslash( $_POST['s'] ) )  );

			} else {
				return null;
			}
		}

		/**
		 * check_for_filter
		 *
		 * @return string|null
		 */
		public static function check_for_filter() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return null;
			}

			if( isset( $_GET['show_logs'], $_GET['_wpnonce'] ) ) {

				$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
				if( ! wp_verify_nonce( $nonce, 'activity-logs-filter' ) ) {
					wp_die( esc_html__( 'Security check: Your nonce did not verify!', 'password-protected' ) );
				} else {
					return sanitize_text_field( wp_unslash( $_GET['show_logs'] ) );
				}
			}
			return null;
		}

		/**
		 * Store the activity report email date window for this request.
		 *
		 * @param array $timestamps Start and end Unix timestamps.
		 * @return void
		 */
		public static function set_email_report_window( array $timestamps ) {
			if ( count( $timestamps ) >= 2 ) {
				self::$email_report_window = array(
					(int) $timestamps[0],
					(int) $timestamps[1],
				);
			}
		}

		/**
		 * Get the activity report email date window (set by sender or fallback to this week).
		 *
		 * @return array{0: int, 1: int}
		 */
		public static function get_email_report_window() {
			if ( is_array( self::$email_report_window ) && count( self::$email_report_window ) >= 2 ) {
				return self::$email_report_window;
			}

			return self::get_time_from_keyword( 'thisweek' );
		}

		/**
		 * Aggregate success/failed attempt counts for a time window.
		 *
		 * @param int $start Start timestamp (inclusive).
		 * @param int $end   End timestamp (inclusive).
		 * @return array{success: int, failed: int, total: int}
		 */
		public static function get_attempt_totals_for_window( $start, $end ) {
			$start = (int) $start;
			$end   = (int) $end;

			if ( $start <= 0 || $end <= 0 || $end < $start ) {
				return array(
					'success' => 0,
					'failed'  => 0,
					'total'   => 0,
				);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . self::$table;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom activity log table; table name is internally constructed from $wpdb->prefix + class constant.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT
						COALESCE( SUM( IF( status = %s, 1, 0 ) ), 0 ) AS success,
						COALESCE( SUM( IF( status = %s, 1, 0 ) ), 0 ) AS failed
					FROM %i
					WHERE CAST( created_at AS UNSIGNED ) BETWEEN %d AND %d',
					'Success',
					'Failure',
					$table_name,
					$start,
					$end
				),
				ARRAY_A
			);

			$success = 0;
			$failed  = 0;
			if ( is_array( $row ) ) {
				$success = isset( $row['success'] ) ? (int) $row['success'] : 0;
				$failed  = isset( $row['failed'] ) ? (int) $row['failed'] : 0;
			}

			return array(
				'success' => $success,
				'failed'  => $failed,
				'total'   => $success + $failed,
			);
		}

		/**
		 * get_time_from_keyword
		 *
		 * @param  mixed $keyword
		 *
		 * @return array
		 */
		public static function get_time_from_keyword( $keyword = '' ) {

			$today                 = strtotime( current_time( 'Y-m-d' ) . ' midnight', self::$now );
			$todays_date           = current_time( 'd' );
			$weekday               = current_time( 'w' );
			$result                = array();

			$keyword               = strtolower( (string) $keyword );

			// Today
			if ( $keyword === 'today' ) {
				$result[] = $today;
				$result[] = self::$now;
			}

			// Yesterday
			elseif ( $keyword === 'yesterday' ) {
				$result[] = strtotime( '-1 day midnight', self::$now );
				$result[] = strtotime( 'today midnight', self::$now );
			}

			// This week
			elseif ( $keyword === 'thisweek' ) {

				$thisweek  = strtotime( '-' . ( $weekday+1 ) . ' days midnight', self::$now );
				if ( get_option( 'start_of_week' ) == $weekday )
					$thisweek = $today;

				$result[] = $thisweek;
				$result[] = self::$now;

			}

			// This month
			elseif ( $keyword === 'thismonth' ) {
				$result[] = strtotime( current_time( 'Y-m-01' ) . ' midnight', self::$now );
				$result[] = self::$now;
			}

			return $result;

		}
	}
}

new Password_Protected_Activity_Logs();