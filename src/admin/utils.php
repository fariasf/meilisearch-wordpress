<?php

use MeiliSearch\Client;

function get_meilisearch_index(){
    $meilisearch_options = get_option( 'meilisearch_option_name' );
    $client = new Client( $meilisearch_options['meilisearch_url_0'], $meilisearch_options['meilisearch_private_key_1'] );
    try {
        $index = $client->index( $meilisearch_options['meilisearch_index_name'] );
    } catch( Exception $e ) {
        $index = $client->createIndex( $meilisearch_options['meilisearch_index_name'] );
    }
    return $index;
}

function index_post_after_update( $post_ID, $post, $update ) {
    index_post( $post );
}

function index_post_after_meta_update( $post, $request ) {
    index_post( $post );
}

function index_post( $post ){
    $index    = get_meilisearch_index();
    $document = apply_filters( 'post_to_document', $post );
    if ( $document ) {
        $index->addDocuments($document);
    }
}

function delete_post_from_index($post_id){
    $index = get_meilisearch_index();
    $index->deleteDocument($post_id);
}

function index_all_posts( $sync = false ){
    $post_types = get_post_types_by_support( 'meilisearch' );
    foreach ( $post_types as $post_type ) {
        index_all_posts_of_type( $post_type, $sync );
    }
}

function index_all_posts_of_type( $post_type, $sync = false ) {
    $index = get_meilisearch_index();

    $query = new WP_Query(
        array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'all',
        )
    );
    
    $documents = array_reduce( $query->posts, function( $carry, $post ) {
        $document = apply_filters( 'post_to_document', $post );
        if ( $document ) {
            $carry[] = $document;
        }
        return $carry;
    }, [] );

    if ( empty( $documents ) ) {
        return;
    }

    try {
        $update = $index->addDocuments($documents);
        
        if ($sync) {
            $index->waitForTask($update['taskUid']);
        }
    } catch (Exception $e) {
        error_log('Meilisearch indexing error: ' . $e->getMessage());
    }

    wp_reset_postdata();
}

function delete_index(){
    $index = get_meilisearch_index();
    $index->delete();
}

function count_indexed(){
    $index = get_meilisearch_index();
    try {
        $count = $index->stats();
        return $count['numberOfDocuments'];
    } catch( Exception $e ) {
        return 0;
    }
}

function post_to_document( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return false;
    }
    if ( $post->post_status !== 'publish' ) {
        return false;
    }
    $categories = [];
    foreach ( $post->post_category as $category ) {
        array_push( $categories, get_cat_name( $category ) );
    }
    $document = [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'content'    => strip_tags( $post->post_content ),
            'img'        => get_the_post_thumbnail_url( $post, array( 100, 100 ) ),
            'url'        => get_the_permalink( $post ),
            'tags'       => $post->tags_input,
            'categories' => $categories,
            'type'       => $post->post_type,
    ];
    return $document;
}
add_filter( 'post_to_document', 'post_to_document' );