<?php

/**
 * Plugin Name:          Ainbae Product Collections for WooCommerce
 * Plugin URI:           https://ainbae.com
 * Description:          Adds a "Collections" taxonomy to WooCommerce — works exactly like Product Categories with full hierarchy, admin panel, and frontend archive support.
 * Version:              1.1.0
 * Requires at least:    5.8
 * Tested up to:       	 6.9
 * Requires PHP:         7.4
 * Author:               Ainbae
 * Author URI:           https://ainbae.com
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          ainbae-collections
 * Domain Path:          /languages
 * WC requires at least: 6.0
 * WC tested up to:      10.7
 *
 * @package Ainbae\Collections
 */


if (! defined('ABSPATH')) {
	exit;
}
// ── Constants ──────────────────────────────────────────────────────────────────
define('AINBAE_COL_VERSION',     '1.1.0');
define('AINBAE_COL_TAXONOMY',    'product_collection');   // Taxonomy name
define('AINBAE_COL_SLUG',        'collection');            // URL slug → /collection/summer/
define('AINBAE_COL_PAGE_OPTION', 'ainbae_col_page_id');   // Option key for collections page
define('AINBAE_COL_FILE',        __FILE__);
define('AINBAE_COL_PATH',        plugin_dir_path(__FILE__));
define('AINBAE_COL_URL',         plugin_dir_url(__FILE__));

// ── Includes ───────────────────────────────────────────────────────────────────
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-taxonomy.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-admin.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-frontend.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-page.php';
require_once AINBAE_COL_PATH . 'includes/class-ainbae-collections-settings.php';

/**
 * Main bootstrap class — singleton.
 */
final class Ainbae_Product_Collections
{

	/** @var self|null */
	private static ?self $instance = null;

	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		register_activation_hook(AINBAE_COL_FILE,   [$this, 'on_activate']);
		register_deactivation_hook(AINBAE_COL_FILE, [$this, 'on_deactivate']);
		add_action('plugins_loaded', [$this, 'boot'], 10);
	}

	/**
	 * Boot — runs after all plugins are loaded so WooCommerce is available.
	 */
	public function boot(): void
	{
		if (! class_exists('WooCommerce')) {
			add_action('admin_notices', [$this, 'notice_wc_missing']);
			return;
		}

		// Register taxonomy as early as possible.
		add_action('init', [Ainbae_Collections_Taxonomy::instance(), 'register'], 5);

		// Boot all feature layers.
		Ainbae_Collections_Admin::instance()->init();
		Ainbae_Collections_Frontend::instance()->init();
		Ainbae_Collections_Page::instance()->init();
		Ainbae_Collections_Settings::instance()->init();
	}

	/**
	 * Activation:
	 *  - Register taxonomy so rewrite rules include the collection slug.
	 *  - Flush rewrite rules.
	 *  - Conflict-safe collection landing page creation (FIX #4).
	 */
	public function on_activate(): void
	{
		Ainbae_Collections_Taxonomy::instance()->register();
		flush_rewrite_rules();
		Ainbae_Collections_Page::maybe_create_page(); // FIX #4
	}

	/** Clean up rewrite rules on deactivation. */
	public function on_deactivate(): void
	{
		flush_rewrite_rules();
	}

	/** Admin notice when WooCommerce is not active. */
	public function notice_wc_missing(): void
	{
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__('Ainbae Product Collections requires WooCommerce to be installed and active.', 'ainbae-collections')
		);
	}
}

// Launch.
Ainbae_Product_Collections::get_instance();
