<?php
/**
 * Post types: signed records (wpw_record) and editable waiver documents
 * (wpw_document). Records are created only by the form handler; documents
 * are edited in the block editor and carry revisions.
 */

defined( 'ABSPATH' ) || exit;

function wpw_register_post_types() {
	register_post_type( 'wpw_record', array(
		'labels'          => array(
			'name'          => __( 'Waivers', 'wp-waiver' ),
			'singular_name' => __( 'Waiver Record', 'wp-waiver' ),
			'menu_name'     => __( 'Waivers', 'wp-waiver' ),
			'all_items'     => __( 'Signed Records', 'wp-waiver' ),
			'edit_item'     => __( 'Waiver Record', 'wp-waiver' ),
			'search_items'  => __( 'Search records', 'wp-waiver' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-edit-page',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'capabilities'    => array(
			// Records only come from the public form.
			'create_posts' => 'do_not_allow',
		),
	) );

	register_post_type( 'wpw_document', array(
		'labels'          => array(
			'name'          => __( 'Waiver Documents', 'wp-waiver' ),
			'singular_name' => __( 'Waiver Document', 'wp-waiver' ),
			'add_new_item'  => __( 'Add New Waiver Document', 'wp-waiver' ),
			'edit_item'     => __( 'Edit Waiver Document', 'wp-waiver' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'edit.php?post_type=wpw_record',
		'show_in_rest'    => true,
		'supports'        => array( 'title', 'editor', 'revisions' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) );
}
add_action( 'init', 'wpw_register_post_types' );

/**
 * Resolve a publishable waiver document: explicit id, else the settings default.
 *
 * @param int $id Document post ID (0 = use the default from settings).
 * @return WP_Post|null
 */
function wpw_resolve_document( $id = 0 ) {
	if ( ! $id ) {
		$settings = wpw_settings();
		$id       = (int) $settings['default_document'];
	}
	if ( ! $id ) {
		return null;
	}
	$post = get_post( $id );
	if ( $post && 'wpw_document' === $post->post_type && 'publish' === $post->post_status ) {
		return $post;
	}
	return null;
}

/**
 * Render a document's agreement HTML (what the signer sees and what gets
 * snapshotted into the record).
 */
function wpw_render_agreement( $doc ) {
	return apply_filters( 'the_content', $doc->post_content );
}
