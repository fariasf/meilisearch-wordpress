<?php

/**
 * MeiliSearch PHP client - WP CLI commands
 */

WP_CLI::add_command( 'meilisearch index', function( $args, $assoc_args ) {
    $meilisearch = new MeiliSearch();
    if ( isset( $assoc_args['all'] ) ) {
        index_all_posts();
    } elseif ( isset( $assoc_args['post_type'] ) ) {
        index_all_posts_of_type( $assoc_args['post_type'], $assoc_args['batch_size'] ?: -1, $assoc_args['page'] ?: 1 );
    }
} );

