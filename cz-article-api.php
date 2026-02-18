<?php
/**
 * Plugin Name: CZ Article API
 * Description: API pubblica per esporre i contenuti dei post in formato JSON.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: CZ
 * Text Domain: cz-article-api
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CZ_Article_API {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'cz-article-api/v1',
			'/post/(?P<slug>[^/]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_by_slug' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'sanitize_callback' => 'sanitize_title',
						'required'          => true,
					),
				),
			)
		);
	}

	public function get_post_by_slug( WP_REST_Request $request ) {
		$slug = (string) $request->get_param( 'slug' );

		if ( '' === $slug ) {
			return new WP_Error(
				'cz_article_api_invalid_slug',
				__( 'Slug non valido.', 'cz-article-api' ),
				array( 'status' => 400 )
			);
		}

		$post = $this->find_published_post_by_slug( $slug );
		if ( ! $post ) {
			return new WP_Error(
				'cz_article_api_not_found',
				__( 'Post non trovato.', 'cz-article-api' ),
				array( 'status' => 404 )
			);
		}

		$subtitle = $this->get_subtitle( $post->ID );
		$volume   = $this->get_primary_volume_title( $post->ID );

		return rest_ensure_response(
			array(
				'author'   => $this->get_author_name( $post ),
				'title'    => $this->normalize_plain_text( get_the_title( $post ) ),
				'subtitle' => '' !== $subtitle ? $subtitle : null,
				'content'  => apply_filters( 'the_content', $post->post_content ),
				'volume'   => '' !== $volume ? $volume : null,
			)
		);
	}

	private function find_published_post_by_slug( $slug ) {
		$query = new WP_Query(
			array(
				'name'                   => $slug,
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'cache_results'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		$post = $query->posts[0];
		return $post instanceof WP_Post ? $post : null;
	}

	private function get_author_name( WP_Post $post ) {
		$author_name = get_the_author_meta( 'display_name', $post->post_author );
		return is_string( $author_name ) ? $this->normalize_plain_text( $author_name ) : '';
	}

	private function get_subtitle( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return '';
		}

		$subtitle = '';

		if ( function_exists( 'get_field' ) ) {
			$acf_subtitle = get_field( 'sottotitolo', $post_id );
			if ( is_string( $acf_subtitle ) ) {
				$subtitle = $acf_subtitle;
			}
		}

		if ( '' === $subtitle ) {
			$meta_subtitle = get_post_meta( $post_id, 'sottotitolo', true );
			if ( is_string( $meta_subtitle ) ) {
				$subtitle = $meta_subtitle;
			}
		}

		return $this->normalize_plain_text( $subtitle );
	}

	private function get_primary_volume_title( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return '';
		}

		$table_name = $wpdb->prefix . 'cz_volume_items';
		$table_ok   = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );
		if ( ! $table_ok ) {
			return '';
		}

		$has_primary = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table_name} LIKE %s",
				'is_primary'
			)
		);

		$order_by = $has_primary ? 'i.is_primary DESC, i.position ASC, i.id ASC' : 'i.position ASC, i.id ASC';

		$volume_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT i.volume_id
				FROM {$table_name} i
				INNER JOIN {$wpdb->posts} p ON p.ID = i.volume_id
				WHERE i.post_id = %d
				AND p.post_type = %s
				ORDER BY {$order_by}
				LIMIT 1",
				$post_id,
				'volume'
			)
		);

		if ( ! $volume_id ) {
			return '';
		}

		$volume_title = get_the_title( $volume_id );
		return is_string( $volume_title ) ? $this->normalize_plain_text( $volume_title ) : '';
	}

	private function normalize_plain_text( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$plain   = wp_strip_all_tags( $decoded, true );
		$plain   = preg_replace( '/\s+/u', ' ', $plain );

		return is_string( $plain ) ? trim( $plain ) : '';
	}
}

new CZ_Article_API();
