<?php
/**
 * Admin: record list columns and the read-only record view (participant
 * details, handwritten signature, and the agreement snapshot as signed).
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'manage_wpw_record_posts_columns', function ( $columns ) {
	return array(
		'cb'       => $columns['cb'],
		'title'    => __( 'Participant', 'wp-waiver' ),
		'w_phone'  => __( 'Phone', 'wp-waiver' ),
		'w_email'  => __( 'Email', 'wp-waiver' ),
		'w_doc'    => __( 'Document', 'wp-waiver' ),
		'w_signed' => __( 'Signed', 'wp-waiver' ),
	);
} );

add_action( 'manage_wpw_record_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'w_phone':
			echo esc_html( get_post_meta( $post_id, '_wpw_phone', true ) );
			break;
		case 'w_email':
			echo esc_html( get_post_meta( $post_id, '_wpw_email', true ) );
			break;
		case 'w_doc':
			$doc_id = (int) get_post_meta( $post_id, '_wpw_doc_id', true );
			echo $doc_id ? esc_html( get_the_title( $doc_id ) ) : '&mdash;';
			break;
		case 'w_signed':
			echo esc_html( get_the_date( 'M j, Y g:i a', $post_id ) );
			break;
	}
}, 10, 2 );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'wpw_record_detail', __( 'Waiver Record', 'wp-waiver' ), function ( $post ) {
		$doc_id = (int) get_post_meta( $post->ID, '_wpw_doc_id', true );
		$rows   = array(
			__( 'Name', 'wp-waiver' )              => get_post_meta( $post->ID, '_wpw_name', true ),
			__( 'Phone', 'wp-waiver' )             => get_post_meta( $post->ID, '_wpw_phone', true ),
			__( 'Email', 'wp-waiver' )             => get_post_meta( $post->ID, '_wpw_email', true ),
			__( 'Signature (typed)', 'wp-waiver' ) => get_post_meta( $post->ID, '_wpw_sig_typed', true ),
			__( 'Signed at', 'wp-waiver' )         => get_the_date( 'F j, Y g:i a', $post->ID ),
			__( 'IP address', 'wp-waiver' )        => get_post_meta( $post->ID, '_wpw_ip', true ),
			__( 'Browser', 'wp-waiver' )           => get_post_meta( $post->ID, '_wpw_ua', true ),
			__( 'Document', 'wp-waiver' )          => $doc_id ? sprintf( '%s (#%d)', get_the_title( $doc_id ), $doc_id ) : '—',
			__( 'Agreement SHA-256', 'wp-waiver' ) => get_post_meta( $post->ID, '_wpw_doc_hash', true ),
		);
		echo '<table class="widefat striped">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</table>';

		$sig_url = get_post_meta( $post->ID, '_wpw_sig_url', true );
		if ( $sig_url ) {
			echo '<p style="margin-bottom:4px"><strong>' . esc_html__( 'Handwritten signature', 'wp-waiver' ) . '</strong></p>';
			echo '<img src="' . esc_url( $sig_url ) . '" alt="' . esc_attr__( 'Handwritten signature', 'wp-waiver' ) . '" style="max-width:440px;border:1px solid #ccc;background:#fff" />';
		}

		if ( '' !== trim( $post->post_content ) ) {
			echo '<details style="margin-top:16px"><summary style="cursor:pointer"><strong>' . esc_html__( 'Agreement as signed (verbatim snapshot)', 'wp-waiver' ) . '</strong></summary>';
			echo '<div style="border:1px solid #ddd;background:#fff;padding:16px 20px;margin-top:8px;max-height:420px;overflow-y:auto">' . wp_kses_post( $post->post_content ) . '</div></details>';
		}

		echo '<p><em>' . esc_html__( 'The agreement acceptance box was checked at submission (required to submit). Editing the waiver document later never changes this record.', 'wp-waiver' ) . '</em></p>';
	}, 'wpw_record', 'normal', 'high' );
} );
