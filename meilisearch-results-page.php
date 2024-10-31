<?php
/*
Plugin Name: Meilisearch Results Page
Plugin URI: https://wordpress.meilisearch.dev
description: Replace the default WordPress Search Results with Meilisearch
Version: 0.1.0
Author: Facundo Farias
Author URI: https://facundofarias.com
License: GPL-v2-or-later
*/

function meilisearch_results_page_init() {        
    add_action( 'pre_get_posts', 'meilisearch_results_page_override_search_query' );
    add_filter( 'the_posts', 'meilisearch_results_page_order_by_relevance', 10, 2 );
    add_filter( 'get_search_query', 'meilisearch_results_page_preserve_search_term' );
}
add_action( 'init', 'meilisearch_results_page_init' );

/**
 * Override the default WP search query to fetch the results provided by Meilisearch.
 */
function meilisearch_results_page_override_search_query( $query ) {
    if ( $query->is_search() && !is_admin() && $query->is_main_query() ) {
        global $meilisearch;
        if ( ! $meilisearch ) {
            $meilisearch = new MeiliSearch();
        }
        try {
            $index   = get_meilisearch_index();
            $results = $index->search( $query->get( 's' ) )->getHits();
            if ( ! empty( $results ) ) {
                $query->set( 's', '' );
                $ids = array_column( $results, 'id' );
                $query->set( 'post__in', $ids );
                $query->set( 'meilisearch_search', true );
            }
        } catch ( Exception $e ) {
            error_log( $e->getMessage() );
        }
    }
}

/**
 * Preserve the search term (used in page title).
 */
function meilisearch_results_page_preserve_search_term( $term ) {
    if ( isset( $_GET['s'] ) ) {
        $term = sanitize_text_field( $_GET['s'] );
    }
    return $term;
}

/**
 * Reorder posts to match Meilisearch results' order.
 */
function meilisearch_results_page_order_by_relevance($posts, $query) {
    if ( $query->get( 'meilisearch_search' ) ) {
        $order = $query->get( 'post__in' );
        usort( $posts, function( $a, $b ) use ( $order ) {
            return array_search( $a->ID, $order ) - array_search( $b->ID, $order );
        });
    }
    return $posts;
}
