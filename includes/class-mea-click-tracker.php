<?php
/**
 * Simple link click tracking for scheduled emails.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SIMESC_Click_Tracker {

	const QUERY_VAR = 'simesc_click';

	/**
	 * Hook redirect handler.
	 */
	public function init() {
		add_action( 'init', array( $this, 'maybe_handle_click' ), 1 );
	}

	/**
	 * Clicks table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'simesc_email_clicks';
	}

	/**
	 * Create / update clicks table.
	 */
	public static function create_table() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email_id bigint(20) UNSIGNED NOT NULL,
			recipient_email varchar(255) NOT NULL DEFAULT '',
			url text NOT NULL,
			clicked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY email_id (email_id),
			KEY clicked_at (clicked_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Wrap http(s) links in HTML so clicks pass through our logger.
	 *
	 * @param string $html           Email HTML.
	 * @param int    $email_id       Campaign post ID.
	 * @param string $recipient_email Recipient address.
	 * @return string
	 */
	public function wrap_links( $html, $email_id, $recipient_email ) {
		$email_id         = absint( $email_id );
		$recipient_email  = sanitize_email( $recipient_email );
		if ( ! $email_id || ! is_email( $recipient_email ) || ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$tracker = $this;
		return preg_replace_callback(
			'/<a\s([^>]*?)href\s*=\s*(["\'])([^"\']+)\2([^>]*)>/i',
			static function ( $m ) use ( $tracker, $email_id, $recipient_email ) {
				$url = html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				if ( ! $tracker->should_track_url( $url ) ) {
					return $m[0];
				}
				$tracked = $tracker->build_tracked_url( $email_id, $recipient_email, $url );
				return '<a ' . $m[1] . 'href=' . $m[2] . esc_url( $tracked ) . $m[2] . $m[4] . '>';
			},
			$html
		);
	}

	/**
	 * Whether a URL should be rewritten for tracking.
	 *
	 * @param string $url Raw href.
	 * @return bool
	 */
	public function should_track_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}
		if ( preg_match( '/^(mailto:|tel:|#|javascript:)/i', $url ) ) {
			return false;
		}
		// Already a tracked link.
		if ( false !== strpos( $url, 'simesc_click=' ) ) {
			return false;
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Build signed tracking redirect URL.
	 *
	 * @param int    $email_id   Campaign ID.
	 * @param string $recipient  Recipient email.
	 * @param string $target_url Destination URL.
	 * @return string
	 */
	public function build_tracked_url( $email_id, $recipient, $target_url ) {
		$email_id  = absint( $email_id );
		$recipient = sanitize_email( $recipient );
		$target    = esc_url_raw( $target_url );

		$payload = $email_id . '|' . $recipient . '|' . $target;
		$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		return add_query_arg(
			array(
				self::QUERY_VAR => '1',
				'e'             => $email_id,
				'r'             => rawurlencode( base64_encode( $recipient ) ),
				'u'             => rawurlencode( base64_encode( $target ) ),
				's'             => $sig,
			),
			home_url( '/' )
		);
	}

	/**
	 * Handle front-end click: verify, log, redirect.
	 */
	public function maybe_handle_click() {
		if ( empty( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$email_id = isset( $_GET['e'] ) ? absint( wp_unslash( $_GET['e'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sig      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$r_raw    = isset( $_GET['r'] ) ? wp_unslash( $_GET['r'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$u_raw    = isset( $_GET['u'] ) ? wp_unslash( $_GET['u'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$recipient = sanitize_email( (string) base64_decode( rawurldecode( (string) $r_raw ), true ) );
		$target    = esc_url_raw( (string) base64_decode( rawurldecode( (string) $u_raw ), true ) );

		if ( ! $email_id || ! $sig || ! is_email( $recipient ) || ! $this->should_track_url( $target ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$payload  = $email_id . '|' . $recipient . '|' . $target;
		$expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected, $sig ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$this->log_click( $email_id, $recipient, $target );

		// External destinations are intentional; URL already validated as http(s) + signed.
		wp_redirect( $target, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Insert a click row.
	 *
	 * @param int    $email_id  Campaign ID.
	 * @param string $recipient Recipient.
	 * @param string $url       Destination.
	 */
	public function log_click( $email_id, $recipient, $url ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			self::table_name(),
			array(
				'email_id'        => absint( $email_id ),
				'recipient_email' => sanitize_email( $recipient ),
				'url'             => esc_url_raw( $url ),
				'clicked_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Click stats for a campaign.
	 *
	 * @param int $email_id Campaign ID.
	 * @return array{total:int,unique_recipients:int,recent:array<int,object>}
	 */
	public static function get_stats( $email_id ) {
		global $wpdb;

		$email_id = absint( $email_id );
		$table    = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
				"SELECT COUNT(*) FROM `{$table}` WHERE email_id = %d",
				$email_id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$unique = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
				"SELECT COUNT(DISTINCT recipient_email) FROM `{$table}` WHERE email_id = %d",
				$email_id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
				"SELECT recipient_email, url, clicked_at FROM `{$table}` WHERE email_id = %d ORDER BY clicked_at DESC LIMIT 20",
				$email_id
			)
		);

		return array(
			'total'              => $total,
			'unique_recipients'  => $unique,
			'recent'             => is_array( $recent ) ? $recent : array(),
		);
	}

	/**
	 * Recent clicks across all campaigns.
	 *
	 * @param int $limit Max rows.
	 * @return array<int,object>
	 */
	public static function get_recent_all( $limit = 50 ) {
		global $wpdb;

		$table = self::table_name();
		$limit = max( 1, min( 200, absint( $limit ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
				"SELECT id, email_id, recipient_email, url, clicked_at FROM `{$table}` ORDER BY clicked_at DESC LIMIT %d",
				$limit
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Per-campaign click totals (for list/overview).
	 *
	 * @return array<int,array{total:int,unique_recipients:int}>
	 */
	public static function get_totals_by_campaign() {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT email_id,
				COUNT(*) AS total,
				COUNT(DISTINCT recipient_email) AS unique_recipients
			FROM `{$table}`
			GROUP BY email_id"
		);

		$out = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[ (int) $row->email_id ] = array(
					'total'             => (int) $row->total,
					'unique_recipients' => (int) $row->unique_recipients,
				);
			}
		}
		return $out;
	}
}
