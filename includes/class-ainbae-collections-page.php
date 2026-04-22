<?php
/**
 * Collections Landing Page — FIX #4
 *
 * Provides:
 *  - [ainbae_collections] shortcode that renders a grid of all collections
 *    (mirrors the "Shop" page but for collections).
 *  - Conflict-safe page creation on activation:
 *      • Checks saved page ID first.
 *      • Looks for existing pages that already use our shortcode.
 *      • Checks if a /collections/ slug page already exists (does NOT overwrite it).
 *      • Only creates a fresh page when no conflicts are found.
 *  - A "Collections Page" setting in WooCommerce → Ainbae Collections settings.
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Page {

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
		// Shortcode
		add_shortcode( 'ainbae_collections', [ $this, 'render_shortcode' ] );

		// Enqueue frontend styles only when shortcode is on the page.
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_styles' ] );

		// Mark the collections page in WooCommerce breadcrumbs.
		add_filter( 'woocommerce_breadcrumb_defaults', [ $this, 'breadcrumb_home_url' ] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Shortcode — [ainbae_collections columns="3" orderby="name" order="ASC"]
	// ══════════════════════════════════════════════════════════════════════════

	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts( [
			'columns'    => 3,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => 0,
			'limit'      => -1,
		], $atts, 'ainbae_collections' );

		$terms = get_terms( [
			'taxonomy'   => AINBAE_COL_TAXONOMY,
			'orderby'    => sanitize_key( $atts['orderby'] ),
			'order'      => strtoupper( $atts['order'] ) === 'DESC' ? 'DESC' : 'ASC',
			'hide_empty' => (int) $atts['hide_empty'],
			'number'     => (int) $atts['limit'] > 0 ? (int) $atts['limit'] : 0,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<p class="ainbae-col-empty woocommerce-info">'
				. esc_html__( 'No collections found.', 'ainbae-collections' )
				. '</p>';
		}

		$cols = max( 1, min( 6, (int) $atts['columns'] ) );

		ob_start();
		?>
		<div class="ainbae-col-grid ainbae-col-grid-<?php echo esc_attr( $cols ); ?> woocommerce columns-<?php echo esc_attr( $cols ); ?>">
			<ul class="products columns-<?php echo esc_attr( $cols ); ?>">
			<?php foreach ( $terms as $term ) :
				$link     = get_term_link( $term, AINBAE_COL_TAXONOMY );
				$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				// Try WC-style thumbnail_id first (if using Woo's term meta), then our own.
				if ( ! $thumb_id ) {
					$thumb_id = (int) get_term_meta( $term->term_id, AINBAE_COL_TAXONOMY . '_thumbnail_id', true );
				}
				$img_src  = $thumb_id
					? wp_get_attachment_image_url( $thumb_id, 'woocommerce_thumbnail' )
					: wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$img_alt  = $thumb_id
					? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true )
					: $term->name;
			?>
			<li class="product-category product ainbae-col-item">
				<a href="<?php echo esc_url( is_wp_error( $link ) ? '#' : $link ); ?>" class="ainbae-col-card woocommerce-loop-category__link">
					<img src="<?php echo esc_url( $img_src ); ?>"
					     alt="<?php echo esc_attr( $img_alt ?: $term->name ); ?>"
					     class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
					     loading="lazy">
					<h2 class="woocommerce-loop-category__title">
						<?php echo esc_html( $term->name ); ?>
						<mark class="count">
							<?php echo esc_html( sprintf(
								/* translators: %d: product count */
								_n( '(%d)', '(%d)', $term->count, 'ainbae-collections' ),
								$term->count
							) ); ?>
						</mark>
					</h2>
					<?php if ( $term->description ) : ?>
						<p class="ainbae-col-card-desc">
							<?php echo esc_html( wp_trim_words( $term->description, 20 ) ); ?>
						</p>
					<?php endif; ?>
				</a>
			</li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Assets
	// ══════════════════════════════════════════════════════════════════════════

	public function maybe_enqueue_styles(): void {
		global $post;

		// Enqueue if the current page contains our shortcode.
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ainbae_collections' ) ) {
			// Re-use WooCommerce's product loop stylesheet so the grid
			// looks identical to the category archive grid — no extra CSS file needed.
			wp_enqueue_style( 'woocommerce-layout' );
			wp_enqueue_style( 'woocommerce-smallscreen' );
			wp_enqueue_style( 'woocommerce-general' );

			// Our own thin stylesheet for the shortcode grid.
			wp_enqueue_style(
				'ainbae-collections-page',
				AINBAE_COL_URL . 'assets/css/collections-page.css',
				[ 'woocommerce-layout' ],
				AINBAE_COL_VERSION
			);
		}
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Breadcrumb — make WooCommerce link "Collections" in breadcrumbs
	// ══════════════════════════════════════════════════════════════════════════

	public function breadcrumb_home_url( array $defaults ): array {
		// Nothing to change on the breadcrumb defaults; the frontend class
		// handles the per-term breadcrumb. Here we just return unchanged.
		return $defaults;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  FIX #4 — Conflict-safe page creation (called from activation hook)
	// ══════════════════════════════════════════════════════════════════════════

	/**
	 * Create the Collections landing page only if no conflict exists.
	 * Safe to call on every activation — idempotent.
	 */
	public static function maybe_create_page(): void {
		// 1. Already have a valid saved page? Done.
		$saved_id = (int) get_option( AINBAE_COL_PAGE_OPTION, 0 );
		if ( $saved_id && get_post( $saved_id ) instanceof WP_Post ) {
			return;
		}

		// 2. Any existing page already using our shortcode? Adopt it.
		$existing_sc = get_posts( [
			'post_type'      => 'page',
			'post_status'    => [ 'publish', 'draft', 'private' ],
			'posts_per_page' => 1,
			'meta_query'     => [],      // no meta needed
			's'              => 'ainbae_collections',
		] );
		if ( ! empty( $existing_sc ) ) {
			update_option( AINBAE_COL_PAGE_OPTION, $existing_sc[0]->ID );
			return;
		}

		// 3. A page with the /collections/ slug already exists?
		//    Save its ID (so breadcrumbs etc. work) but DO NOT touch its content.
		$slug_page = get_page_by_path( 'collections', OBJECT, 'page' );
		if ( $slug_page instanceof WP_Post ) {
			update_option( AINBAE_COL_PAGE_OPTION, $slug_page->ID );
			return;
		}

		// 4. No conflict — create a fresh Collections page.
		$page_id = wp_insert_post( [
			'post_title'     => __( 'Collections', 'ainbae-collections' ),
			'post_name'      => 'collections',
			'post_content'   => '[ainbae_collections]',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
		] );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( AINBAE_COL_PAGE_OPTION, $page_id );
		}
	}
}
