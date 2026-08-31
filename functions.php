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

// wolf-portfolio (the plugin that registers the "work" CPT) is generalist,
// shared across other production sites, and doesn't set show_in_graphql —
// see register-work-fields.php's own note on not touching plugin files.
// Can't fix this via the usual `register_post_type_args` filter either:
// the plugin calls register_post_type() at its own file's top level, which
// runs before this theme's functions.php is even loaded, so a filter added
// here would never be in place in time. Mutate the already-registered post
// type object directly instead — WPGraphQL builds its schema per-request,
// well after `init`, so this is still in time for it.
// Priority 1: the "work" CPT is already registered by this point (the
// plugin calls register_post_type() at its own file's top level, long
// before any init hook runs), but WPGraphQL itself reads/snapshots
// show_in_graphql etc. from its own init hook (default priority 10) — this
// has to win that race and run first, or WPGraphQL never sees the change.
add_action( 'init', function () {
	$work_post_type = get_post_type_object( 'work' );

	if ( ! $work_post_type ) {
		return;
	}

	$work_post_type->show_in_graphql     = true;
	$work_post_type->graphql_single_name = 'work';
	$work_post_type->graphql_plural_name = 'works';

	// This WPGraphQL version (2.20.0) also reads these two — confirmed via
	// wp-debug.log ("Undefined property: WP_Post_Type::$graphql_kind" /
	// "...$graphql_register_root_connection" from PostObject.php /
	// PostObjects.php). Without graphql_register_root_connection explicitly
	// true, WPGraphQL registers the Work object type but skips generating
	// the works/work root query connection fields entirely — which was the
	// actual missing piece the three properties above weren't enough for.
	$work_post_type->graphql_kind                     = 'object';
	$work_post_type->graphql_register_root_connection = true;
	// A separate property from the one above: traced directly from
	// RootQuery.php — the singular work(id: ...) root field is built from
	// \WPGraphQL::get_allowed_post_types('objects', ['graphql_register_root_field' => true]),
	// a completely different filter/property than the plural connection.
	// graphql_register_root_connection alone fixed `works`; this fixes `work`.
	$work_post_type->graphql_register_root_field = true;

	// Same gap, same fix, for the work_type taxonomy (wolf-portfolio's
	// register_taxonomy() call has no graphql args either — see
	// wfolio-register-taxonomy.php) — the frontend queries it as
	// `workTypes` on Work.
	$work_type_taxonomy = get_taxonomy( 'work_type' );

	if ( ! $work_type_taxonomy ) {
		return;
	}

	$work_type_taxonomy->show_in_graphql     = true;
	$work_type_taxonomy->graphql_single_name = 'workType';
	$work_type_taxonomy->graphql_plural_name = 'workTypes';
	$work_type_taxonomy->graphql_kind        = 'object';
	// Mirrors the CPT fix: show_in_graphql/names/kind alone got the
	// WorkType object type registered but not the workTypes connection
	// field on Work — this is the property that specifically wires up a
	// connection rather than just the type itself.
	$work_type_taxonomy->graphql_register_root_connection = true;
	// Same singular/plural split as the CPT above — set proactively so the
	// singular workType(id: ...) field doesn't need its own separate
	// round-trip once something queries it.
	$work_type_taxonomy->graphql_register_root_field = true;
}, 1 );

require_once __DIR__ . '/inc/register-work-fields.php';
