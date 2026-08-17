<?php
/**
 * Plugin Name: WP Waiver
 * Description: Standalone online range waiver — renders the Waiver & Release Agreement with a handwritten signature pad via the [wp_waiver_form] shortcode, stores signed waivers as a private post type, and notifies the admin email. No third-party form plugins.
 * Version: 1.0.0
 * Author: Old Steel Arsenal
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_WAIVER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_WAIVER_URL', plugin_dir_url( __FILE__ ) );

/**
 * URL of the public waiver page (redirect target after submit).
 */
function aaa_waiver_page_url() {
	return apply_filters( 'aaa_waiver_page_url', home_url( '/waiver/' ) );
}

/* -------------------------------------------------------------------------
 * Post type
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'aaa_waiver', array(
		'labels'          => array( 'name' => 'Waivers', 'singular_name' => 'Waiver' ),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-edit-page',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) );
} );

/* -------------------------------------------------------------------------
 * Assets & shortcode
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	wp_register_script(
		'wp-waiver-sigpad',
		WP_WAIVER_URL . 'assets/signature-pad.js',
		array(),
		(string) filemtime( WP_WAIVER_DIR . 'assets/signature-pad.js' ),
		true
	);
} );

add_shortcode( 'wp_waiver_form', function () {
	wp_enqueue_script( 'wp-waiver-sigpad' );
	ob_start();
	include WP_WAIVER_DIR . 'templates/form.php';
	return ob_get_clean();
} );

/* -------------------------------------------------------------------------
 * Submission handler
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_aaa_waiver', 'aaa_waiver_handle_submit' );
add_action( 'admin_post_aaa_waiver', 'aaa_waiver_handle_submit' );

function aaa_waiver_handle_submit() {
	$back = aaa_waiver_page_url();

	if ( ! isset( $_POST['aaa_waiver_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['aaa_waiver_nonce'] ), 'aaa_waiver' ) ) {
		wp_safe_redirect( add_query_arg( 'err', '1', $back ) );
		exit;
	}

	// Honeypot: silently accept bots without storing anything.
	if ( ! empty( $_POST['aaa_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'signed', '1', $back ) );
		exit;
	}

	$fields = array();
	foreach ( array( 'w_name', 'w_phone', 'w_email', 'w_sig' ) as $key ) {
		$fields[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}
	$fields['w_email'] = sanitize_email( $fields['w_email'] );
	$accepted          = ! empty( $_POST['w_ack'] );

	// Handwritten signature: a PNG data URL from the signature pad.
	$sig_png = '';
	$sig_raw = isset( $_POST['w_sig_img'] ) ? (string) wp_unslash( $_POST['w_sig_img'] ) : '';
	if ( '' !== $sig_raw && strlen( $sig_raw ) <= 500000 && 0 === strpos( $sig_raw, 'data:image/png;base64,' ) ) {
		$decoded = base64_decode( substr( $sig_raw, 22 ), true );
		if ( false !== $decoded && 0 === strncmp( $decoded, "\x89PNG", 4 ) ) {
			$sig_png = $decoded;
		}
	}

	if ( in_array( '', $fields, true ) || ! $accepted || ! is_email( $fields['w_email'] ) || strlen( $fields['w_sig'] ) < 3 || '' === $sig_png ) {
		wp_safe_redirect( add_query_arg( 'err', '1', $back ) );
		exit;
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'aaa_waiver',
		'post_status' => 'private',
		'post_title'  => sprintf( '%s — %s', $fields['w_name'], wp_date( 'Y-m-d H:i' ) ),
	), true );

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'err', '1', $back ) );
		exit;
	}

	$meta = array(
		'_w_name'  => $fields['w_name'],
		'_w_phone' => $fields['w_phone'],
		'_w_email' => $fields['w_email'],
		'_w_sig'   => $fields['w_sig'],
		'_w_ip'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'_w_ua'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, $k, $v );
	}

	// Persist the drawn signature as a PNG in uploads. Random suffix keeps the
	// URL unguessable (uploads are publicly reachable by path).
	$sig_file = '';
	$upload   = wp_upload_bits( sprintf( 'waiver-sig-%d-%s.png', $post_id, wp_generate_password( 12, false ) ), null, $sig_png );
	if ( empty( $upload['error'] ) ) {
		$sig_file = $upload['file'];
		update_post_meta( $post_id, '_w_sig_file', $upload['file'] );
		update_post_meta( $post_id, '_w_sig_url', $upload['url'] );
	}

	wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[Waiver] %s', $fields['w_name'] ),
		sprintf(
			"%s signed the Range Waiver online.\n\nName: %s\nPhone: %s\nEmail: %s\nSigned: %s\n\nHandwritten signature attached.\nView in admin: %s",
			$fields['w_name'], $fields['w_name'], $fields['w_phone'], $fields['w_email'],
			wp_date( 'F j, Y g:i a' ),
			admin_url( 'edit.php?post_type=aaa_waiver' )
		),
		array(),
		$sig_file ? array( $sig_file ) : array()
	);

	wp_safe_redirect( add_query_arg( 'signed', '1', $back ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Admin: list columns + record view
 * ---------------------------------------------------------------------- */

add_filter( 'manage_aaa_waiver_posts_columns', function ( $columns ) {
	return array(
		'cb'       => $columns['cb'],
		'title'    => 'Participant',
		'w_phone'  => 'Phone',
		'w_email'  => 'Email',
		'w_signed' => 'Signed',
	);
} );

add_action( 'manage_aaa_waiver_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'w_phone':
			echo esc_html( get_post_meta( $post_id, '_w_phone', true ) );
			break;
		case 'w_email':
			echo esc_html( get_post_meta( $post_id, '_w_email', true ) );
			break;
		case 'w_signed':
			echo esc_html( get_the_date( 'M j, Y g:i a', $post_id ) );
			break;
	}
}, 10, 2 );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'aaa_waiver_detail', 'Waiver Record', function ( $post ) {
		$rows = array(
			'Name'              => get_post_meta( $post->ID, '_w_name', true ),
			'Phone'             => get_post_meta( $post->ID, '_w_phone', true ),
			'Email'             => get_post_meta( $post->ID, '_w_email', true ),
			'Signature (typed)' => get_post_meta( $post->ID, '_w_sig', true ),
			'Signed at'         => get_the_date( 'F j, Y g:i a', $post->ID ),
			'IP address'        => get_post_meta( $post->ID, '_w_ip', true ),
			'Browser'           => get_post_meta( $post->ID, '_w_ua', true ),
		);
		echo '<table class="widefat striped">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</table>';
		$sig_url = get_post_meta( $post->ID, '_w_sig_url', true );
		if ( $sig_url ) {
			echo '<p style="margin-bottom:4px"><strong>Handwritten signature</strong></p>';
			echo '<img src="' . esc_url( $sig_url ) . '" alt="Handwritten signature" style="max-width:440px;border:1px solid #ccc;background:#fff" />';
		}
		echo '<p><em>The Waiver &amp; Release Agreement acceptance box was checked at submission (required to submit).</em></p>';
	}, 'aaa_waiver', 'normal', 'high' );
} );
