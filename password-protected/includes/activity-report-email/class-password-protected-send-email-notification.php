<?php


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Password_Protected_Send_Email_Notification' ) ) {
	class Password_Protected_Send_Email_Notification {
		private static $instance;

		private function __construct() {
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
			add_action( 'init', array( $this, 'init_cron' ) );
			add_action( 'password_protected_email_notification_hook', array( $this, 'send_email_notification' ) );
		}

		public function cron_schedules( $schedules ) {
			$schedules['password_protected_email_notification'] = array(
				'interval' => Password_protected_Activity_Report_Settings::get_report_interval(),
				'display'  => __( 'Password Protected Email Notification Interval', 'password-protected' ),
			);
			return $schedules;
		}

		public function init_cron() {
			if ( ! wp_next_scheduled( 'password_protected_email_notification_hook' ) ) {
				wp_schedule_event( time(), 'password_protected_email_notification', 'password_protected_email_notification_hook' );
			}
		}

		public function send_email_notification() {
			$timestamps = Password_Protected_Activity_Logs::get_time_from_keyword( 'thisweek' );
			Password_Protected_Activity_Logs::set_email_report_window( $timestamps );

			$start = isset( $timestamps[0] ) ? (int) $timestamps[0] : 0;
			$end   = isset( $timestamps[1] ) ? (int) $timestamps[1] : 0;
			$stats = Password_Protected_Activity_Logs::get_attempt_totals_for_window( $start, $end );

			$success_attempts = $stats['success'];
			$failed_attempts  = $stats['failed'];
			$total_attempts   = $stats['total'];

			$template = $this->get_template( $success_attempts, $failed_attempts, $total_attempts, $timestamps );
			$headers  = $this->get_html_headers();
			$subject  = sprintf(
				'[ %s, %s ]',
				get_bloginfo( 'name' ),
				__( 'Password Protected Activity Log Notification', 'password-protected' )
			);

			return wp_mail( get_option( 'admin_email' ), $subject, $template, $headers );
		}

		/**
		 * Build the activity report HTML email body.
		 *
		 * The template fires {@see 'password_protected_activity_notification_content'} with:
		 * - total   (int)   Total attempts in the report window.
		 * - success (int)   Successful attempts.
		 * - failed  (int)   Failed attempts.
		 * - time    (array) Report window [ start_timestamp, end_timestamp ].
		 *
		 * @param int   $success Successful attempts.
		 * @param int   $failed  Failed attempts.
		 * @param int   $total   Total attempts.
		 * @param array $time    Report window timestamps.
		 * @return string HTML email body.
		 */
		private function get_template( $success, $failed, $total, $time ) {
			ob_start();
			require PASSWORD_PROTECTED_DIR . 'templates/emails/activity-notification.php';
			return ob_get_clean();
		}
		private function get_html_headers() {
			$headers = array(
				'Content-type: text/html',
			);

			return implode( "\r\n", apply_filters( 'password_protected_email_headers', $headers ) );
		}

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

Password_Protected_Send_Email_Notification::get_instance();
