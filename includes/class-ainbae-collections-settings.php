<?php
/**
 * Plugin settings page — WooCommerce → Ainbae Collections
 *
 * Settings:
 *  - Collections Page (select which page acts as the landing page)
 *
 * @package Ainbae\Collections
 */

defined( 'ABSPATH' ) || exit;

class Ainbae_Collections_Settings {

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
		add_action( 'admin_menu',  [ $this, 'register_menu' ] );
		add_action( 'admin_init',  [ $this, 'save_settings' ] );
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Menu
	// ══════════════════════════════════════════════════════════════════════════

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Ainbae Collections Settings', 'ainbae-collections' ),
			__( 'Ainbae Collections', 'ainbae-collections' ),
			'manage_woocommerce',
			'ainbae-collections-settings',
			[ $this, 'render_page' ]
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Save
	// ══════════════════════════════════════════════════════════════════════════

	public function save_settings(): void {
		if (
			! isset( $_POST['ainbae_col_settings_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['ainbae_col_settings_nonce'] ) ),
				'ainbae_col_save_settings_action'
			) ||
			! current_user_can( 'manage_woocommerce' ) ||
			! isset( $_POST['ainbae_col_save'] )
		) {
			return;
		}

		// Collections page
		$page_id = isset( $_POST[ AINBAE_COL_PAGE_OPTION ] )
			? absint( wp_unslash( $_POST[ AINBAE_COL_PAGE_OPTION ] ) )
			: 0;
		update_option( AINBAE_COL_PAGE_OPTION, $page_id );

		wp_safe_redirect( add_query_arg( [
			'page'    => 'ainbae-collections-settings',
			'updated' => '1',
			'_nonce'  => wp_create_nonce( 'ainbae_col_updated' ),
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	// ══════════════════════════════════════════════════════════════════════════
	//  Render
	// ══════════════════════════════════════════════════════════════════════════

	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Access denied.', 'ainbae-collections' ) );
		}

		$saved_page_id = (int) get_option( AINBAE_COL_PAGE_OPTION, 0 );

		$updated = '';
		if (
			isset( $_GET['updated'], $_GET['_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ), 'ainbae_col_updated' )
		) {
			$updated = sanitize_text_field( wp_unslash( $_GET['updated'] ) );
		}
		?>
		<div class="wrap" id="ainbae-col-settings-wrap">
			<h1><?php esc_html_e( 'Ainbae Collections Settings', 'ainbae-collections' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e( '✓ Settings saved.', 'ainbae-collections' ); ?></strong></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'ainbae_col_save_settings_action', 'ainbae_col_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>

						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( AINBAE_COL_PAGE_OPTION ); ?>">
									<?php esc_html_e( 'Collections Page', 'ainbae-collections' ); ?>
								</label>
							</th>
							<td>
								<?php
								wp_dropdown_pages( [
									'name'              => AINBAE_COL_PAGE_OPTION,
									'id'                => AINBAE_COL_PAGE_OPTION,
									'selected'          => $saved_page_id,
									'show_option_none'  => __( '— Select a page —', 'ainbae-collections' ),
									'option_none_value' => '0',
								] );
								?>
								<p class="description">
									<?php
									esc_html_e(
										'The page that lists all your collections. Add the [ainbae_collections] shortcode to it.',
										'ainbae-collections'
									);
									?>
									<?php if ( $saved_page_id && get_post( $saved_page_id ) ) : ?>
										&nbsp;
										<a href="<?php echo esc_url( get_permalink( $saved_page_id ) ); ?>" target="_blank">
											<?php esc_html_e( 'View page ↗', 'ainbae-collections' ); ?>
										</a>
										&nbsp;|&nbsp;
										<a href="<?php echo esc_url( get_edit_post_link( $saved_page_id ) ); ?>">
											<?php esc_html_e( 'Edit page', 'ainbae-collections' ); ?>
										</a>
									<?php endif; ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Shortcode', 'ainbae-collections' ); ?></th>
							<td>
								<code>[ainbae_collections]</code>
								<p class="description">
									<?php esc_html_e( 'Paste this into any page or widget to display your collections grid. Accepts optional attributes:', 'ainbae-collections' ); ?>
									<br>
									<code>[ainbae_collections columns="3" orderby="name" order="ASC" hide_empty="0" limit="-1"]</code>
								</p>
							</td>
						</tr>

					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="ainbae_col_save" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'ainbae-collections' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
