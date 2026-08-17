<?php
/**
 * Submission pipeline: security gates (nonce → honeypot → time-trap →
 * rate-limit → captcha), field + signature validation, tamper-evident record
 * creation (agreement snapshot + SHA-256), notification and receipt mail.
 *
 * Bot verdicts fake success: redirect to the confirmation state, store
 * nothing, send nothing — no feedback signal for the attacker.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_wpw_submit', 'wpw_handle_submit' );
add_action( 'admin_post_wpw_submit', 'wpw_handle_submit' );

function wpw_redirect_flag( $return_url, $flag ) {
	wp_safe_redirect( add_query_arg( 'wpw', $flag, $return_url ) );
	exit;
}

function wpw_handle_submit() {
	$raw_return = isset( $_POST['wpw_return'] ) ? esc_url_raw( wp_unslash( $_POST['wpw_return'] ) ) : '';
	$return     = wp_validate_redirect( $raw_return, home_url( '/' ) );

	if ( ! isset( $_POST['wpw_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpw_nonce'] ), 'wpw_submit' ) ) {
		wpw_redirect_flag( $return, 'err' );
	}

	$doc_id = isset( $_POST['wpw_doc'] ) ? absint( $_POST['wpw_doc'] ) : 0;
	$doc    = wpw_resolve_document( $doc_id );
	if ( ! $doc || $doc->ID !== $doc_id ) {
		wpw_redirect_flag( $return, 'err' );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	// --- Anti-bot gates (silent fake success) -----------------------------
	if ( ! empty( $_POST['wpw_website'] ) ) { // Honeypot.
		wpw_redirect_flag( $return, 'signed' );
	}
	$token = isset( $_POST['wpw_tt'] ) ? sanitize_text_field( wp_unslash( $_POST['wpw_tt'] ) ) : '';
	if ( ! wpw_timetrap_ok( $token, $doc_id ) ) {
		wpw_redirect_flag( $return, 'signed' );
	}
	if ( ! wpw_rate_limit_ok( $ip ) ) {
		wpw_redirect_flag( $return, 'signed' );
	}

	// --- Captcha (visible failure: could be an honest mistake) ------------
	if ( ! wpw_captcha_verify() ) {
		wpw_redirect_flag( $return, 'captcha' );
	}

	// --- Fields -----------------------------------------------------------
	$fields = array();
	foreach ( array( 'wpw_name', 'wpw_phone', 'wpw_email', 'wpw_sig_typed' ) as $key ) {
		$fields[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}
	$fields['wpw_email'] = sanitize_email( $fields['wpw_email'] );
	$accepted            = ! empty( $_POST['wpw_ack'] );

	// Handwritten signature: PNG data URL from the pad.
	$sig_png = '';
	$sig_raw = isset( $_POST['wpw_sig_img'] ) ? (string) wp_unslash( $_POST['wpw_sig_img'] ) : '';
	if ( '' !== $sig_raw && strlen( $sig_raw ) <= 500000 && 0 === strpos( $sig_raw, 'data:image/png;base64,' ) ) {
		$decoded = base64_decode( substr( $sig_raw, 22 ), true );
		if ( false !== $decoded && 0 === strncmp( $decoded, "\x89PNG", 4 ) ) {
			$sig_png = $decoded;
		}
	}

	if ( in_array( '', $fields, true ) || ! $accepted || ! is_email( $fields['wpw_email'] ) || strlen( $fields['wpw_sig_typed'] ) < 3 || '' === $sig_png ) {
		wpw_redirect_flag( $return, 'err' );
	}

	// --- Record with agreement snapshot -----------------------------------
	$snapshot = wpw_render_agreement( $doc );

	$post_id = wp_insert_post( array(
		'post_type'    => 'wpw_record',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s — %s', $fields['wpw_name'], wp_date( 'Y-m-d H:i' ) ),
		'post_content' => $snapshot,
	), true );

	if ( is_wp_error( $post_id ) ) {
		wpw_redirect_flag( $return, 'err' );
	}

	$meta = array(
		'_wpw_name'      => $fields['wpw_name'],
		'_wpw_phone'     => $fields['wpw_phone'],
		'_wpw_email'     => $fields['wpw_email'],
		'_wpw_sig_typed' => $fields['wpw_sig_typed'],
		'_wpw_ip'        => $ip,
		'_wpw_ua'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		'_wpw_doc_id'    => $doc->ID,
		'_wpw_doc_hash'  => hash( 'sha256', $snapshot ),
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, $k, $v );
	}

	// Signature PNG into uploads; random suffix keeps the URL unguessable.
	$sig_file = '';
	$upload   = wp_upload_bits( sprintf( 'wpw-sig-%d-%s.png', $post_id, wp_generate_password( 12, false ) ), null, $sig_png );
	if ( empty( $upload['error'] ) ) {
		$sig_file = $upload['file'];
		update_post_meta( $post_id, '_wpw_sig_file', $upload['file'] );
		update_post_meta( $post_id, '_wpw_sig_url', $upload['url'] );
	}

	// --- Mail -------------------------------------------------------------
	$settings = wpw_settings();
	$notify   = $settings['notify_email'] ? $settings['notify_email'] : get_option( 'admin_email' );

	wp_mail(
		$notify,
		sprintf( /* translators: %s: signer name */ __( '[Waiver] %s', 'wp-waiver' ), $fields['wpw_name'] ),
		sprintf(
			/* translators: 1: name, 2: document title, 3: phone, 4: email, 5: date, 6: admin url */
			__( "%1\$s signed \"%2\$s\" online.\n\nName: %1\$s\nPhone: %3\$s\nEmail: %4\$s\nSigned: %5\$s\n\nHandwritten signature attached.\nView in admin: %6\$s", 'wp-waiver' ),
			$fields['wpw_name'],
			$doc->post_title,
			$fields['wpw_phone'],
			$fields['wpw_email'],
			wp_date( 'F j, Y g:i a' ),
			admin_url( 'edit.php?post_type=wpw_record' )
		),
		array(),
		$sig_file ? array( $sig_file ) : array()
	);

	if ( $settings['send_receipt'] ) {
		wp_mail(
			$fields['wpw_email'],
			sprintf( /* translators: %s: document title */ __( 'Your signed waiver — %s', 'wp-waiver' ), $doc->post_title ),
			sprintf(
				/* translators: 1: name, 2: date, 3: site name, 4: agreement text */
				__( "Hi %1\$s,\n\nThis is a receipt for the waiver you signed on %2\$s at %3\$s. The agreement you accepted is copied below for your records.\n\n----------------------------------------\n\n%4\$s", 'wp-waiver' ),
				$fields['wpw_name'],
				wp_date( 'F j, Y g:i a' ),
				get_bloginfo( 'name' ),
				wp_strip_all_tags( $snapshot )
			)
		);
	}

	wpw_redirect_flag( $return, 'signed' );
}
