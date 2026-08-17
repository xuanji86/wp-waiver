<?php
/**
 * Plugin Name: WP Waiver
 * Plugin URI: https://github.com/xuanji86/wp-waiver
 * Description: Online waiver signing — admin-editable waiver documents, a handwritten signature pad, tamper-evident signed records, and layered anti-bot protection.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Old Steel Arsenal
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-waiver
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_WAIVER_VERSION', '1.0.0' );
define( 'WP_WAIVER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_WAIVER_URL', plugin_dir_url( __FILE__ ) );

require_once WP_WAIVER_DIR . 'includes/post-types.php';
require_once WP_WAIVER_DIR . 'includes/settings.php';
require_once WP_WAIVER_DIR . 'includes/antibot.php';
require_once WP_WAIVER_DIR . 'includes/captcha.php';
require_once WP_WAIVER_DIR . 'includes/form.php';
require_once WP_WAIVER_DIR . 'includes/submit.php';
require_once WP_WAIVER_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, function () {
	wpw_register_post_types();
	flush_rewrite_rules();

	// The plugin ships no legal text. Seed a draft sample so the flow is
	// discoverable on a fresh install.
	$existing = get_posts( array(
		'post_type'   => 'wpw_document',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );
	if ( ! $existing ) {
		wp_insert_post( array(
			'post_type'    => 'wpw_document',
			'post_status'  => 'draft',
			'post_title'   => __( 'Sample Waiver Agreement', 'wp-waiver' ),
			'post_content' => '<p>' . esc_html__( 'Replace this sample with your organization’s waiver and release agreement, publish the document, then select it in Waivers → Settings (or pass its ID to the [wp_waiver_form id="…"] shortcode). Have legal counsel review the text before going live.', 'wp-waiver' ) . '</p>',
		) );
	}
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
