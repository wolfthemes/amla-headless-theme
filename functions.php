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
} );

require_once __DIR__ . '/inc/register-work-fields.php';
