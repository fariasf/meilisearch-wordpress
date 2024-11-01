<?php
/*
Plugin Name: Meilisearch WordPress integration
Plugin URI: https://facundofarias.com
description: Integrate Meilisearch with WordPress
Version: 0.2.0
Author: Facundo Farias
Author URI: https://facundofarias.com
License: GPL-v2-or-later
*/

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/admin/meilisearch_admin.php';
require_once __DIR__ . '/src/admin/utils.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/src/cli.php';
}

function meilisearch_init() {
	add_action( 'wp_insert_post', 'index_post_after_update', 1000, 3 );
	add_action( 'rest_after_insert_post', 'index_post_after_meta_update', 1000, 2 );
	add_action( 'wp_trash_post', 'delete_post_from_index' );

	$public_post_types = get_post_types( array( 'public' => true ) );
	foreach ( $public_post_types as $post_type ) {
		add_post_type_support( $post_type, 'meilisearch' );
	}
}
add_action( 'init', 'meilisearch_init' );

if ( is_admin() ) {
	$meilisearch = new MeiliSearch();
}
