<?php
/**
 * ML Archi (Headless) theme functions.
 *
 * Headless: no templates render to visitors. The faustwp plugin handles
 * CORS and redirects frontend requests to the Next.js app once its
 * Frontend URL is set in Settings → Faust. This file only sets up
 * theme supports needed for consistent admin/GraphQL behavior.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'editor-style.css' );

	// Post formats for the Work CPT (standard, gallery, video) — exposed to
	// the frontend via WPGraphQL's built-in Post_Format taxonomy support.
	add_theme_support( 'post-formats', array( 'gallery', 'video' ) );
	add_post_type_support( 'work', 'post-formats' );
} );

// add_post_type_support() alone doesn't attach the post_format taxonomy to a
// CPT — it still needs registering for the 'work' object type. Priority 20
// so this runs after the 'work' CPT itself is registered on init.
add_action( 'init', function () {
	register_taxonomy_for_object_type( 'post_format', 'work' );
}, 20 );

require_once __DIR__ . '/inc/register-work-fields.php';
