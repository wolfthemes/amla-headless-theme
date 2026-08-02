<?php
/**
 * Work gallery metabox for ML Archi.
 *
 * A hand-rolled WP media-library gallery picker for the "Work" CPT. The
 * wolf-portfolio plugin's shared Metabox class is array-driven but has no
 * multi-image field type (its `image` type stores a single attachment ID,
 * `repeatable` is plain text rows), so this one small case is built here in
 * the theme rather than by touching the plugin. See BOUNDARIES.md.
 *
 * Stores `_work_gallery` as a comma-separated list of attachment IDs, ordered.
 * The order is authored by drag-reorder in the admin and preserved through to
 * the `workGallery` GraphQL field (see register-work-fields.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ML_Archi_Work_Gallery_Metabox {

	const META_KEY   = '_work_gallery';
	const NONCE_NAME = 'ml_archi_work_gallery_nonce';
	const POST_TYPE  = 'work';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register the metabox on the Work edit screen.
	 */
	public function add_meta_box() {
		add_meta_box(
			'ml_archi_work_gallery',
			esc_html__( 'Galerie du projet', 'ml-archi' ),
			array( $this, 'render' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Enqueue the media library + sortable + picker script, scoped to the
	 * Work post editor only.
	 */
	public function enqueue( $hook ) {

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'ml-archi-work-gallery',
			get_template_directory_uri() . '/assets/js/work-gallery.js',
			array( 'jquery', 'jquery-ui-sortable', 'media-editor' ),
			wp_get_theme()->get( 'Version' ),
			true
		);

		wp_localize_script(
			'ml-archi-work-gallery',
			'mlArchiWorkGallery',
			array(
				'frameTitle'  => esc_html__( 'Galerie du projet', 'ml-archi' ),
				'buttonText'  => esc_html__( 'Ajouter à la galerie', 'ml-archi' ),
				'removeLabel' => esc_html__( 'Retirer', 'ml-archi' ),
			)
		);
	}

	/**
	 * Render the metabox: a hidden CSV input, a sortable thumbnail preview,
	 * and the "add / edit" button.
	 */
	public function render( $post ) {

		wp_nonce_field( 'ml_archi_save_work_gallery', self::NONCE_NAME );

		$raw = get_post_meta( $post->ID, self::META_KEY, true );
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
		?>
		<div class="ml-archi-gallery" data-ml-archi-gallery>

			<input
				type="hidden"
				name="<?php echo esc_attr( self::META_KEY ); ?>"
				class="ml-archi-gallery-ids"
				value="<?php echo esc_attr( implode( ',', $ids ) ); ?>"
			/>

			<ul class="ml-archi-gallery-preview">
				<?php foreach ( $ids as $id ) : ?>
					<?php $img = wp_get_attachment_image( $id, 'thumbnail' ); ?>
					<?php if ( $img ) : ?>
						<li class="ml-archi-gallery-item" data-id="<?php echo esc_attr( $id ); ?>">
							<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns safe markup. ?>
							<button type="button" class="ml-archi-gallery-remove" aria-label="<?php esc_attr_e( 'Retirer', 'ml-archi' ); ?>">&times;</button>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>

			<p>
				<button type="button" class="button ml-archi-gallery-add">
					<?php esc_html_e( 'Ajouter / modifier la galerie', 'ml-archi' ); ?>
				</button>
			</p>

			<p class="description">
				<?php esc_html_e( 'Glissez-déposez pour réordonner. L\'ordre définit la mise en page du projet.', 'ml-archi' ); ?>
			</p>
		</div>

		<style>
			.ml-archi-gallery-preview {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin: 0 0 12px;
				padding: 0;
				list-style: none;
			}
			.ml-archi-gallery-item {
				position: relative;
				width: 90px;
				height: 90px;
				margin: 0;
				cursor: move;
				border: 1px solid #dcdcde;
				background: #f6f7f7;
			}
			.ml-archi-gallery-item img {
				width: 100%;
				height: 100%;
				object-fit: cover;
				display: block;
			}
			.ml-archi-gallery-item.ui-sortable-helper { opacity: .85; }
			.ml-archi-gallery-remove {
				position: absolute;
				top: -8px;
				right: -8px;
				width: 20px;
				height: 20px;
				padding: 0;
				line-height: 18px;
				border-radius: 50%;
				border: 1px solid #dcdcde;
				background: #fff;
				color: #d63638;
				cursor: pointer;
			}
		</style>
		<?php
	}

	/**
	 * Persist the ordered attachment IDs.
	 */
	public function save( $post_id, $post ) {

		// Nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), 'ml_archi_save_work_gallery' ) ) {
			return;
		}

		// Autosave / caps.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::META_KEY ] ) ) {
			return;
		}

		$raw = sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) );
		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::META_KEY );
			return;
		}

		update_post_meta( $post_id, self::META_KEY, implode( ',', $ids ) );
	}
}

new ML_Archi_Work_Gallery_Metabox();
