<?php
/**
 * ML Archi-specific fields on the "Work" CPT (wolf-portfolio plugin).
 *
 * The plugin is generalist and shared across multiple production sites — it
 * only owns "Client" and "Link". Everything client-specific for ML Archi
 * lives here instead, reusing the plugin's shared Metabox class rather than
 * touching plugin files. See BOUNDARIES.md at the wp-vip repo root.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function() {

	$ml_archi_work_meta_args = array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'auth_callback' => function() {
			return current_user_can( 'edit_posts' );
		},
	);

	register_post_meta( 'work', '_work_program', $ml_archi_work_meta_args );
	register_post_meta( 'work', '_work_surface', $ml_archi_work_meta_args );
	register_post_meta( 'work', '_work_completion', $ml_archi_work_meta_args );
	register_post_meta( 'work', '_work_video_url', $ml_archi_work_meta_args );
} );

add_action( 'graphql_register_types', function() {

	if ( ! function_exists( 'register_graphql_field' ) ) {
		return;
	}

	register_graphql_field( 'Work', 'workProgram', array(
		'type' => 'String',
		'description' => esc_html__( 'Program for this work.', 'ml-archi' ),
		'resolve' => function( $post ) {
			return get_post_meta( $post->ID, '_work_program', true );
		},
	) );

	register_graphql_field( 'Work', 'workSurface', array(
		'type' => 'String',
		'description' => esc_html__( 'Surface area for this work.', 'ml-archi' ),
		'resolve' => function( $post ) {
			return get_post_meta( $post->ID, '_work_surface', true );
		},
	) );

	register_graphql_field( 'Work', 'workCompletion', array(
		'type' => 'String',
		'description' => esc_html__( 'Completion status/date for this work.', 'ml-archi' ),
		'resolve' => function( $post ) {
			return get_post_meta( $post->ID, '_work_completion', true );
		},
	) );

	register_graphql_field( 'Work', 'workVideoUrl', array(
		'type' => 'String',
		'description' => esc_html__( 'Video URL for this work.', 'ml-archi' ),
		'resolve' => function( $post ) {
			return get_post_meta( $post->ID, '_work_video_url', true );
		},
	) );
} );

// Reuses the plugin's shared Metabox class (array-driven, from wolf-portfolio)
// instead of hand-rolling a new metabox — this renders as its own box,
// separate from the plugin's own "Work Details" (Client/Link).
if ( class_exists( '\WolfPortfolio\Admin\Metabox' ) ) {
	new \WolfPortfolio\Admin\Metabox( array(
		'ML Archi Work Details' => array(
			'title' => esc_html__( 'Détails complémentaires', 'ml-archi' ),
			'page' => array( 'work' ),
			'metafields' => array(
				array(
					'label' => esc_html__( 'Programme', 'ml-archi' ),
					'id' => '_work_program',
					'type' => 'text',
				),
				array(
					'label' => esc_html__( 'Surface', 'ml-archi' ),
					'id' => '_work_surface',
					'type' => 'text',
				),
				array(
					'label' => esc_html__( 'Réalisation', 'ml-archi' ),
					'id' => '_work_completion',
					'type' => 'text',
				),
				array(
					'label' => esc_html__( 'Vidéo', 'ml-archi' ),
					'id' => '_work_video_url',
					'type' => 'file',
				),
			),
		),
	) );
}

/**
 * Cosmetic-only relabel of the plugin's "Client" field to "Maître d'ouvrage"
 * for this theme, without touching the plugin's own metabox definition.
 * ponytail: DOM text swap via JS, not a real i18n/label mechanism — replace
 * with a proper filter in the plugin if it ever grows a hook for this.
 */
add_action( 'admin_footer', function() {

	$screen = get_current_screen();

	if ( ! $screen || 'work' !== $screen->post_type ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		var label = document.querySelector('label[for="_work_client"]');
		if ( label ) {
			label.textContent = <?php echo wp_json_encode( esc_html__( "Maître d'ouvrage", 'ml-archi' ) ); ?>;
		}
	});
	</script>
	<?php
} );
