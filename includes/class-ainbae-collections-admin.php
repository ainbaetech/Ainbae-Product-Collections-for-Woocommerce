<?php
/**
 * Admin-side functionality.
 *
 * - Products → Collections submenu (one entry only).
 * - Side meta box on product edit.
 * - One Collections column in Products list table.
 * - Thumbnail uploader on Add New AND Edit Collection forms (Issue #3).
 * - Filter dropdown.
 * - HPOS compatibility.
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Admin {

	/** @var self|null */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		// Menu.
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Meta box on product edit screen.
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ), 100 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_collections' ), 10, 2 );

		// Products list table — one column.
		// Use manage_edit-product_columns (canonical WooCommerce hook) so we
		// run after WooCommerce adds its own columns and after other plugins,
		// giving us a stable position right after Categories.
		add_filter( 'manage_edit-product_columns',          array( $this, 'add_list_column' ), 20 );
		add_action( 'manage_product_posts_custom_column',   array( $this, 'render_list_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_column_sortable' ) );

		// Filter dropdown.
		add_action( 'restrict_manage_posts', array( $this, 'add_collection_filter_dropdown' ), 20 );

		// ── Issue #3: Thumbnail on Add New and Edit collection forms ──────────
		add_action( AINBAE_COL_TAXONOMY . '_add_form_fields',  array( $this, 'render_thumbnail_add_field' ) );
		add_action( AINBAE_COL_TAXONOMY . '_edit_form_fields', array( $this, 'render_thumbnail_edit_field' ) );
		add_action( 'created_' . AINBAE_COL_TAXONOMY,         array( $this, 'save_thumbnail' ) );
		add_action( 'edited_' . AINBAE_COL_TAXONOMY,          array( $this, 'save_thumbnail' ) );

		// Assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// HPOS.
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Menu — one entry under Products.
	// ══════════════════════════════════════════════════════════════════════════

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Product Collections', 'ainbae-product-collections-for-woocommerce' ),
			__( 'Collections', 'ainbae-product-collections-for-woocommerce' ),
			'manage_product_terms',
			'edit-tags.php?taxonomy=' . AINBAE_COL_TAXONOMY . '&post_type=product'
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Meta box on product edit
	// ══════════════════════════════════════════════════════════════════════════

	public function register_metabox(): void {
		remove_meta_box( AINBAE_COL_TAXONOMY . 'div', 'product', 'side' );
		remove_meta_box( AINBAE_COL_TAXONOMY . 'div', 'product', 'normal' );

		add_meta_box(
			AINBAE_COL_TAXONOMY . 'div',
			__( 'Collections', 'ainbae-product-collections-for-woocommerce' ),
			array( $this, 'render_metabox' ),
			'product',
			'side',
			'default'
		);
	}

	public function render_metabox( WP_Post $post ): void {
		$taxonomy   = AINBAE_COL_TAXONOMY;
		$tax_obj    = get_taxonomy( $taxonomy );
		$post_terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		$popular = get_terms( array(
			'taxonomy'   => $taxonomy,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 10,
			'hide_empty' => true,
		) );

		$all_terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'orderby'    => 'name',
			'hide_empty' => false,
		) );

		$show_tabs = ! empty( $popular );
		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv ainbae-col-metabox">

			<ul id="<?php echo esc_attr( $taxonomy ); ?>-tabs" class="category-tabs">
				<li class="tabs">
					<a href="#<?php echo esc_attr( $taxonomy ); ?>-all">
						<?php esc_html_e( 'All Collections', 'ainbae-product-collections-for-woocommerce' ); ?>
					</a>
				</li>
				<?php if ( $show_tabs ) : ?>
				<li class="hide-if-no-js">
					<a href="#<?php echo esc_attr( $taxonomy ); ?>-pop">
						<?php esc_html_e( 'Most Used', 'ainbae-product-collections-for-woocommerce' ); ?>
					</a>
				</li>
				<?php endif; ?>
			</ul>

			<div id="<?php echo esc_attr( $taxonomy ); ?>-all" class="tabs-panel">
				<?php if ( empty( $all_terms ) ) : ?>
					<p class="description" style="padding:5px 0 0;">
						<?php esc_html_e( 'No collections yet. Add one below.', 'ainbae-product-collections-for-woocommerce' ); ?>
					</p>
				<?php else : ?>
				<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist"
				    data-wp-lists="list:<?php echo esc_attr( $taxonomy ); ?>"
				    class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist( $post->ID, array(
						'taxonomy'      => $taxonomy,
						'selected_cats' => $post_terms,
						'popular_cats'  => array(),
						'checked_ontop' => true,
					) );
					?>
				</ul>
				<?php endif; ?>
			</div>

			<?php if ( $show_tabs ) : ?>
			<div id="<?php echo esc_attr( $taxonomy ); ?>-pop" class="tabs-panel" style="display:none;">
				<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist-pop" class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist( $post->ID, array(
						'taxonomy'      => $taxonomy,
						'selected_cats' => $post_terms,
						'popular_cats'  => is_array( $popular ) ? wp_list_pluck( $popular, 'term_id' ) : array(),
						'checked_ontop' => false,
					) );
					?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if ( $tax_obj && current_user_can( $tax_obj->cap->edit_terms ) ) : ?>
			<div id="<?php echo esc_attr( $taxonomy ); ?>-adder" class="wp-hidden-children">
				<a id="<?php echo esc_attr( $taxonomy ); ?>-add-toggle"
				   href="#<?php echo esc_attr( $taxonomy ); ?>-add"
				   class="hide-if-no-js taxonomy-add-new">
					+ <?php esc_html_e( 'Add New Collection', 'ainbae-product-collections-for-woocommerce' ); ?>
				</a>
				<p id="<?php echo esc_attr( $taxonomy ); ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo esc_attr( $taxonomy ); ?>">
						<?php esc_html_e( 'Collection Name', 'ainbae-product-collections-for-woocommerce' ); ?>
					</label>
					<input type="text"
					       name="new<?php echo esc_attr( $taxonomy ); ?>"
					       id="new<?php echo esc_attr( $taxonomy ); ?>"
					       class="form-required form-input-tip"
					       value="<?php esc_attr_e( 'New collection name', 'ainbae-product-collections-for-woocommerce' ); ?>"
					       aria-required="true" />
					<label class="screen-reader-text" for="new<?php echo esc_attr( $taxonomy ); ?>_parent">
						<?php esc_html_e( 'Parent Collection', 'ainbae-product-collections-for-woocommerce' ); ?>
					</label>
					<?php
					wp_dropdown_categories( array(
						'taxonomy'         => $taxonomy,
						'hide_empty'       => 0,
						'name'             => 'new' . $taxonomy . '_parent',
						'id'               => 'new' . $taxonomy . '_parent',
						'orderby'          => 'name',
						'hierarchical'     => true,
						'show_option_none' => '&mdash; ' . esc_html__( 'Parent Collection', 'ainbae-product-collections-for-woocommerce' ) . ' &mdash;',
					) );
					?>
					<input type="button"
					       id="<?php echo esc_attr( $taxonomy ); ?>-add-submit"
					       data-wp-lists="add:<?php echo esc_attr( $taxonomy ); ?>checklist:<?php echo esc_attr( $taxonomy ); ?>-add"
					       class="button category-add-submit"
					       value="<?php esc_attr_e( 'Add New Collection', 'ainbae-product-collections-for-woocommerce' ); ?>" />
					<?php wp_nonce_field( 'add-' . $taxonomy, '_ajax_nonce-add-' . $taxonomy, false ); ?>
					<span id="<?php echo esc_attr( $taxonomy ); ?>-ajax-response"></span>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save_collections( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-post_' . $post_id ) ) {
			return;
		}
		$term_ids = array();
		if ( ! empty( $_POST['tax_input'][ AINBAE_COL_TAXONOMY ] ) ) {
			$term_ids = array_map( 'absint', (array) $_POST['tax_input'][ AINBAE_COL_TAXONOMY ] );
		}
		wp_set_post_terms( $post_id, $term_ids, AINBAE_COL_TAXONOMY );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Products list — one Collections column.
	// ══════════════════════════════════════════════════════════════════════════

	public function add_list_column( array $columns ): array {
		// Remove any auto-generated taxonomy column (belt-and-suspenders).
		unset( $columns[ 'taxonomy-' . AINBAE_COL_TAXONOMY ] );

		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'product_cat' === $key ) {
				$new[ AINBAE_COL_TAXONOMY ] = __( 'Collections', 'ainbae-product-collections-for-woocommerce' );
			}
		}
		if ( ! isset( $new[ AINBAE_COL_TAXONOMY ] ) ) {
			$new[ AINBAE_COL_TAXONOMY ] = __( 'Collections', 'ainbae-product-collections-for-woocommerce' );
		}
		return $new;
	}

	public function render_list_column( string $column, int $post_id ): void {
		if ( AINBAE_COL_TAXONOMY !== $column ) {
			return;
		}
		$terms = get_the_terms( $post_id, AINBAE_COL_TAXONOMY );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<span aria-hidden="true">&mdash;</span>';
			return;
		}
		$links = array();
		foreach ( $terms as $term ) {
			$url     = add_query_arg( array( 'post_type' => 'product', AINBAE_COL_TAXONOMY => $term->slug ), admin_url( 'edit.php' ) );
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $term->name ) );
		}
		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function make_column_sortable( array $columns ): array {
		$columns[ AINBAE_COL_TAXONOMY ] = AINBAE_COL_TAXONOMY;
		return $columns;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Filter dropdown.
	// ══════════════════════════════════════════════════════════════════════════

	public function add_collection_filter_dropdown( string $post_type ): void {
		if ( 'product' !== $post_type ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET[ AINBAE_COL_TAXONOMY ] ) ? sanitize_key( $_GET[ AINBAE_COL_TAXONOMY ] ) : '';
		wp_dropdown_categories( array(
			'show_option_all' => __( 'Filter by collection', 'ainbae-product-collections-for-woocommerce' ),
			'taxonomy'        => AINBAE_COL_TAXONOMY,
			'name'            => AINBAE_COL_TAXONOMY,
			'orderby'         => 'name',
			'selected'        => $selected,
			'hide_empty'      => true,
			'hierarchical'    => true,
			'value_field'     => 'slug',
			'show_count'      => true,
		) );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Issue #3 — Thumbnail on Add New Collection form.
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Renders the thumbnail upload field on the "Add New Collection" screen.
	 * Mirrors WooCommerce's own Product Category thumbnail field exactly.
	 */
	public function render_thumbnail_add_field(): void {
		?>
		<div class="form-field term-thumbnail-wrap">
			<label for="ainbae_col_thumbnail_id">
				<?php esc_html_e( 'Thumbnail', 'ainbae-product-collections-for-woocommerce' ); ?>
			</label>
			<div id="ainbae-col-thumb-preview" class="ainbae-col-thumb-preview" style="margin-bottom:10px;"></div>
			<input type="hidden"
			       id="ainbae_col_thumbnail_id"
			       name="ainbae_col_thumbnail_id"
			       value="">
			<?php wp_nonce_field( 'ainbae_col_save_thumbnail', 'ainbae_col_thumbnail_nonce' ); ?>
			<button type="button" class="button ainbae-col-upload-btn">
				<?php esc_html_e( 'Upload / Choose Image', 'ainbae-product-collections-for-woocommerce' ); ?>
			</button>
			<button type="button" class="button ainbae-col-remove-btn" style="display:none;margin-left:4px;">
				<?php esc_html_e( 'Remove Image', 'ainbae-product-collections-for-woocommerce' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Thumbnail shown on the Collections page grid.', 'ainbae-product-collections-for-woocommerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders the thumbnail upload field on the "Edit Collection" screen.
	 *
	 * @param WP_Term $term Current collection term.
	 */
	public function render_thumbnail_edit_field( WP_Term $term ): void {
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$img_src  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
		?>
		<tr class="form-field term-thumbnail-wrap">
			<th scope="row">
				<label for="ainbae_col_thumbnail_id">
					<?php esc_html_e( 'Thumbnail', 'ainbae-product-collections-for-woocommerce' ); ?>
				</label>
			</th>
			<td>
				<div id="ainbae-col-thumb-preview" class="ainbae-col-thumb-preview" style="margin-bottom:10px;">
					<?php if ( $img_src ) : ?>
						<img src="<?php echo esc_url( $img_src ); ?>" style="max-width:150px;display:block;border-radius:4px;" alt="">
					<?php endif; ?>
				</div>
				<input type="hidden"
				       id="ainbae_col_thumbnail_id"
				       name="ainbae_col_thumbnail_id"
				       value="<?php echo esc_attr( $thumb_id ?: '' ); ?>">
				<?php wp_nonce_field( 'ainbae_col_save_thumbnail', 'ainbae_col_thumbnail_nonce' ); ?>
				<button type="button" class="button ainbae-col-upload-btn">
					<?php esc_html_e( 'Upload / Choose Image', 'ainbae-product-collections-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button ainbae-col-remove-btn" style="<?php echo $thumb_id ? '' : 'display:none;'; ?>margin-left:4px;">
					<?php esc_html_e( 'Remove Image', 'ainbae-product-collections-for-woocommerce' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Thumbnail shown on the Collections page grid.', 'ainbae-product-collections-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the thumbnail term meta for both create and edit actions.
	 *
	 * @param int $term_id Term ID being saved.
	 */
	public function save_thumbnail( int $term_id ): void {
		if (
			! isset( $_POST['ainbae_col_thumbnail_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['ainbae_col_thumbnail_nonce'] ) ),
				'ainbae_col_save_thumbnail'
			)
		) {
			return;
		}

		$thumb_id = isset( $_POST['ainbae_col_thumbnail_id'] )
			? absint( wp_unslash( $_POST['ainbae_col_thumbnail_id'] ) )
			: 0;

		if ( $thumb_id ) {
			update_term_meta( $term_id, 'thumbnail_id', $thumb_id );
		} else {
			delete_term_meta( $term_id, 'thumbnail_id' );
		}
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Assets.
	// ══════════════════════════════════════════════════════════════════════════

	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_product_list    = 'edit.php' === $hook && 'product' === $screen->post_type;
		$is_product_edit    = in_array( $hook, array( 'post.php', 'post-new.php' ), true )
		                      && 'product' === $screen->post_type;
		$is_collections_tax = in_array( $hook, array( 'edit-tags.php', 'term.php' ), true )
		                      && AINBAE_COL_TAXONOMY === $screen->taxonomy;

		if ( $is_product_list || $is_product_edit || $is_collections_tax ) {
			wp_enqueue_style(
				'ainbae-collections-admin',
				AINBAE_COL_URL . 'assets/css/admin.css',
				array(),
				AINBAE_COL_VERSION
			);
		}

		// Thumbnail media uploader — only on the taxonomy add/edit screens.
		if ( $is_collections_tax ) {
			wp_enqueue_media();
			wp_enqueue_script(
				'ainbae-collections-admin-js',
				AINBAE_COL_URL . 'assets/js/admin.js',
				array( 'jquery', 'media-upload', 'thickbox' ),
				AINBAE_COL_VERSION,
				true
			);
			wp_localize_script(
				'ainbae-collections-admin-js',
				'ainbaeColAdmin',
				array(
					'title'  => __( 'Choose Collection Image', 'ainbae-product-collections-for-woocommerce' ),
					'button' => __( 'Use this image', 'ainbae-product-collections-for-woocommerce' ),
				)
			);
		}
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  HPOS.
	// ══════════════════════════════════════════════════════════════════════════

	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				AINBAE_COL_FILE,
				true
			);
		}
	}
}
