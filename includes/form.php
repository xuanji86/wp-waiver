<?php
/**
 * Front end: asset registration and the [wp_waiver_form] shortcode.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style(
		'wp-waiver',
		WP_WAIVER_URL . 'assets/wp-waiver.css',
		array(),
		(string) filemtime( WP_WAIVER_DIR . 'assets/wp-waiver.css' )
	);
	wp_register_script(
		'wp-waiver-sigpad',
		WP_WAIVER_URL . 'assets/signature-pad.js',
		array(),
		(string) filemtime( WP_WAIVER_DIR . 'assets/signature-pad.js' ),
		true
	);
	$captcha_url = wpw_captcha_script_url();
	if ( $captcha_url ) {
		wp_register_script( 'wp-waiver-captcha', $captcha_url, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
} );

add_shortcode( 'wp_waiver_form', function ( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'wp_waiver_form' );
	$doc  = wpw_resolve_document( (int) $atts['id'] );

	if ( ! $doc ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p><em>' . esc_html__( 'WP Waiver: no published waiver document found. Publish one under Waivers → Waiver Documents and select it in Waivers → Settings, or pass its ID to the shortcode.', 'wp-waiver' ) . '</em></p>';
		}
		return '';
	}

	wp_enqueue_style( 'wp-waiver' );
	wp_enqueue_script( 'wp-waiver-sigpad' );
	if ( wpw_captcha_provider() ) {
		wp_enqueue_script( 'wp-waiver-captcha' );
	}

	ob_start();
	include WP_WAIVER_DIR . 'templates/form.php';
	return ob_get_clean();
} );
