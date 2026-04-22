<?php
/**
 * Admin-side functionality:
 *  - Products → Collections submenu
 *  - Side meta box on product edit (identical UX to Product Categories)
 *  - Collections column in Products list table
 *  - Inline "Add New Collection" with parent dropdown
 *  - HPOS compatibility declaration
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
		// ── Menu ─────────────────────────────────────────────────────────────
		add_action( 'admin_menu', [ $this, 'register_menu' ] );

		// ── Meta box ─────────────────────────────────────────────────────────
		// Priority 100 → runs after WP auto-adds the taxonomy meta box (priority 10)
		// so we can cleanly remove it and add our own to the side column.
		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ], 100 );

		// ── Save ─────────────────────────────────────────────────────────────
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_collections' ], 10, 2 );

		// ── Product list table columns ────────────────────────────────────────
		add_filter( 'manage_edit-product_columns',         [ $this, 'add_list_column' ] );
		add_action( 'manage_product_posts_custom_column',  [ $this, 'render_list_column' ], 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns',[ $this, 'make_column_sortable' ] );

		// ── Filter by collection in product list ──────────────────────────────
		add_action( 'restrict_manage_posts', [ $this, 'add_collection_filter_dropdown' ], 20 );

		// ── Admin assets ─────────────────────────────────────────────────────
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// ── HPOS compatibility ─────────────────────────────────────────────────
		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Menu
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Add "Collections" under Products → Collections in the WP admin sidebar.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Product Collections', 'ainbae-collections' ),
			__( 'Collections', 'ainbae-collections' ),
			'manage_product_terms',
			'edit-tags.php?taxonomy=' . AINBAE_COL_TAXONOMY . '&post_type=product'
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Meta box
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Remove the WordPress auto-generated meta box and register our own on the
	 * side column — exactly as WooCommerce does for product_cat.
	 */
	public function register_metabox(): void {
		// Remove WP's auto-generated box (it may land in normal or side).
		remove_meta_box( AINBAE_COL_TAXONOMY . 'div', 'product', 'side' );
		remove_meta_box( AINBAE_COL_TAXONOMY . 'div', 'product', 'normal' );

		add_meta_box(
			AINBAE_COL_TAXONOMY . 'div',
			__( 'Collections', 'ainbae-collections' ),
			[ $this, 'render_metabox' ],
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render the Collections meta box.
	 * Mirrors WooCommerce's product_cat meta box — tabs, checkboxes, inline add form.
	 */
	public function render_metabox( WP_Post $post ): void {
		$taxonomy   = AINBAE_COL_TAXONOMY;
		$tax_obj    = get_taxonomy( $taxonomy );
		$post_terms = wp_get_post_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );

		$popular = get_terms( [
			'taxonomy'   => $taxonomy,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 10,
			'hide_empty' => true,
		] );

		$all_terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'orderby'    => 'name',
			'hide_empty' => false,
		] );

		$show_tabs = ! empty( $popular );
		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>"
		     class="categorydiv ainbae-col-metabox">

			<?php /* ── Tabs ── */ ?>
			<ul id="<?php echo esc_attr( $taxonomy ); ?>-tabs" class="category-tabs">
				<li class="tabs">
					<a href="#<?php echo esc_attr( $taxonomy ); ?>-all">
						<?php esc_html_e( 'All Collections', 'ainbae-collections' ); ?>
					</a>
				</li>
				<?php if ( $show_tabs ) : ?>
				<li class="hide-if-no-js">
					<a href="#<?php echo esc_attr( $taxonomy ); ?>-pop">
						<?php esc_html_e( 'Most Used', 'ainbae-collections' ); ?>
					</a>
				</li>
				<?php endif; ?>
			</ul>

			<?php /* ── All Collections panel ── */ ?>
			<div id="<?php echo esc_attr( $taxonomy ); ?>-all" class="tabs-panel">
				<?php if ( empty( $all_terms ) ) : ?>
					<p class="description" style="padding:5px 0 0;">
						<?php esc_html_e( 'No collections yet. Add one below.', 'ainbae-collections' ); ?>
					</p>
				<?php else : ?>
				<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist"
				    data-wp-lists="list:<?php echo esc_attr( $taxonomy ); ?>"
				    class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist( $post->ID, [
						'taxonomy'      => $taxonomy,
						'selected_cats' => $post_terms,
						'popular_cats'  => [],
						'checked_ontop' => true,
					] );
					?>
				</ul>
				<?php endif; ?>
			</div>

			<?php /* ── Most Used panel ── */ ?>
			<?php if ( $show_tabs ) : ?>
			<div id="<?php echo esc_attr( $taxonomy ); ?>-pop"
			     class="tabs-panel" style="display:none;">
				<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist-pop"
				    class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist( $post->ID, [
						'taxonomy'      => $taxonomy,
						'selected_cats' => $post_terms,
						'popular_cats'  => is_array( $popular ) ? wp_list_pluck( $popular, 'term_id' ) : [],
						'checked_ontop' => false,
					] );
					?>
				</ul>
			</div>
			<?php endif; ?>

			<?php /* ── Inline Add New Collection form ── */ ?>
			<?php if ( $tax_obj && current_user_can( $tax_obj->cap->edit_terms ) ) : ?>
			<div id="<?php echo esc_attr( $taxonomy ); ?>-adder" class="wp-hidden-children">

				<a id="<?php echo esc_attr( $taxonomy ); ?>-add-toggle"
				   href="#<?php echo esc_attr( $taxonomy ); ?>-add"
				   class="hide-if-no-js taxonomy-add-new">
					+ <?php esc_html_e( 'Add New Collection', 'ainbae-collections' ); ?>
				</a>

				<p id="<?php echo esc_attr( $taxonomy ); ?>-add"
				   class="category-add wp-hidden-child">

					<label class="screen-reader-text"
					       for="new<?php echo esc_attr( $taxonomy ); ?>">
						<?php esc_html_e( 'Collection Name', 'ainbae-collections' ); ?>
					</label>
					<input type="text"
					       name="new<?php echo esc_attr( $taxonomy ); ?>"
					       id="new<?php echo esc_attr( $taxonomy ); ?>"
					       class="form-required form-input-tip"
					       value="<?php esc_attr_e( 'New collection name', 'ainbae-collections' ); ?>"
					       aria-required="true" />

					<label class="screen-reader-text"
					       for="new<?php echo esc_attr( $taxonomy ); ?>_parent">
						<?php esc_html_e( 'Parent Collection', 'ainbae-collections' ); ?>
					</label>
					<?php
					wp_dropdown_categories( [
						'taxonomy'         => $taxonomy,
						'hide_empty'       => 0,
						'name'             => 'new' . $taxonomy . '_parent',
						'id'               => 'new' . $taxonomy . '_parent',
						'orderby'          => 'name',
						'hierarchical'     => true,
						'show_option_none' => '&mdash; ' . esc_html__( 'Parent Collection', 'ainbae-collections' ) . ' &mdash;',
					] );
					?>

					<input type="button"
					       id="<?php echo esc_attr( $taxonomy ); ?>-add-submit"
					       data-wp-lists="add:<?php echo esc_attr( $taxonomy ); ?>checklist:<?php echo esc_attr( $taxonomy ); ?>-add"
					       class="button category-add-submit"
					       value="<?php esc_attr_e( 'Add New Collection', 'ainbae-collections' ); ?>" />

					<?php wp_nonce_field( 'add-' . $taxonomy, '_ajax_nonce-add-' . $taxonomy, false ); ?>

					<span id="<?php echo esc_attr( $taxonomy ); ?>-ajax-response"></span>
				</p>

			</div>
			<?php endif; ?>

		</div>
		<?php
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Saving
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Persist selected collections when a product is saved via WooCommerce.
	 * WordPress would usually handle tax_input[] automatically, but WooCommerce's
	 * save hook skips it — so we do it explicitly here.
	 *
	 * @param int      $post_id Product post ID.
	 * @param \WP_Post $post    Product post object.
	 */
	public function save_collections( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-post_' . $post_id ) ) {
			return;
		}

		$taxonomy = AINBAE_COL_TAXONOMY;

		// Collect submitted term IDs (checkboxes send tax_input[taxonomy][]).
		$term_ids = [];
		if ( ! empty( $_POST['tax_input'][ $taxonomy ] ) ) {
			$term_ids = array_map( 'absint', (array) $_POST['tax_input'][ $taxonomy ] );
		}

		wp_set_post_terms( $post_id, $term_ids, $taxonomy );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Products list table — Collections column
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Insert the Collections column immediately after the Categories column.
	 *
	 * @param  array<string,string> $columns
	 * @return array<string,string>
	 */
	public function add_list_column( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'product_cat' === $key ) {
				$new[ AINBAE_COL_TAXONOMY ] = __( 'Collections', 'ainbae-collections' );
			}
		}
		// If product_cat column doesn't exist, just append.
		if ( ! isset( $new[ AINBAE_COL_TAXONOMY ] ) ) {
			$new[ AINBAE_COL_TAXONOMY ] = __( 'Collections', 'ainbae-collections' );
		}
		return $new;
	}

	/**
	 * Render the Collections column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Product post ID.
	 */
	public function render_list_column( string $column, int $post_id ): void {
		if ( AINBAE_COL_TAXONOMY !== $column ) {
			return;
		}

		$terms = get_the_terms( $post_id, AINBAE_COL_TAXONOMY );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<span aria-hidden="true">&mdash;</span>';
			return;
		}

		$links = array_map(
			static function ( \WP_Term $term ): string {
				$url = add_query_arg(
					[
						'post_type'          => 'product',
						AINBAE_COL_TAXONOMY  => $term->slug,
					],
					admin_url( 'edit.php' )
				);
				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( $url ),
					esc_html( $term->name )
				);
			},
			$terms
		);

		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param  array<string,string> $columns
	 * @return array<string,string>
	 */
	public function make_column_sortable( array $columns ): array {
		$columns[ AINBAE_COL_TAXONOMY ] = AINBAE_COL_TAXONOMY;
		return $columns;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Filter dropdown in product list
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Add a "Filter by Collection" dropdown in the Products list table toolbar.
	 *
	 * @param string $post_type Current post type.
	 */
	public function add_collection_filter_dropdown( string $post_type ): void {
		if ( 'product' !== $post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET[ AINBAE_COL_TAXONOMY ] ) ? sanitize_key( $_GET[ AINBAE_COL_TAXONOMY ] ) : '';

		wp_dropdown_categories( [
			'show_option_all' => __( 'Filter by collection', 'ainbae-collections' ),
			'taxonomy'        => AINBAE_COL_TAXONOMY,
			'name'            => AINBAE_COL_TAXONOMY,
			'orderby'         => 'name',
			'selected'        => $selected,
			'hide_empty'      => true,
			'hierarchical'    => true,
			'value_field'     => 'slug',
			'show_count'      => true,
		] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Assets
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Enqueue admin CSS on the product edit screen and collections taxonomy screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_product_edit = in_array( $hook, [ 'post.php', 'post-new.php' ], true )
		                   && 'product' === $screen->post_type;

		$is_collections_tax = ( 'edit-tags.php' === $hook || 'term.php' === $hook )
		                      && AINBAE_COL_TAXONOMY === $screen->taxonomy;

		if ( $is_product_edit || $is_collections_tax ) {
			wp_enqueue_style(
				'ainbae-collections-admin',
				AINBAE_COL_URL . 'assets/css/admin.css',
				[],
				AINBAE_COL_VERSION
			);
		}
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  HPOS
	// ══════════════════════════════════════════════════════════════════════════

	/** Declare compatibility with WooCommerce High-Performance Order Storage. */
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
