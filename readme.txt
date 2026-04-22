=== Ainbae Product Collections for WooCommerce ===
Contributors: ainbae
Tags: woocommerce, collections, taxonomy, products, product collections
Requires at least:  5.8
Tested up to:       6.9
Requires PHP:       7.4
Stable tag:         1.0.0
WC requires at least: 6.0
WC tested up to: 10.7
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.ainbae.com/donate

Adds a "Collections" taxonomy to WooCommerce, working exactly like Product Categories.

== Description ==

Ainbae Product Collections adds a Collections taxonomy to WooCommerce products. Collection archive pages inherit your theme's exact product category layout — same sidebar behaviour, same CSS, same template.

= Features =
* Collections taxonomy (hierarchical, like product categories)
* Collection archive pages that look identical to your Product Category pages
* Collections landing page with [ainbae_collections] shortcode
* One "Collections" column in the Products list (no duplicates)
* One "Collections" menu item under Products (no duplicates)
* Filter products by collection in the admin Products list
* Breadcrumb support
* WooCommerce HPOS compatible

= Shortcode =
Use [ainbae_collections] on any page to display a grid of all collections.

Optional attributes:
* columns — number of columns (default: 3)
* orderby — sort field: name, count, slug (default: name)
* order — ASC or DESC (default: ASC)
* hide_empty — 0 or 1 (default: 0)
* limit — max number of collections, -1 for all (default: -1)

Example:
[ainbae_collections columns="4" orderby="count" order="DESC"]

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate via Plugins → Installed Plugins
3. A "Collections" page is created automatically at /collections/
4. Go to WooCommerce → Ainbae Collections to configure the settings

== Changelog ==

= 1.1.0 =
* FIX: Collections no longer appeared twice in the Products admin menu
* FIX: "Collections" and "Product Collections" columns no longer both appear in the Products list table — now only one "Collections" column
* FIX: Collection archive pages now use the exact same template and layout as Product Category pages (no unwanted sidebar, inherits theme settings correctly)
* NEW: Collections landing page — [ainbae_collections] shortcode creates a shop-like grid of all your collections
* NEW: Conflict-safe page creation on activation (respects existing /collections/ pages)
* NEW: WooCommerce → Ainbae Collections settings page to assign the Collections Page
* NEW: Breadcrumbs on collection archives now include the Collections page as parent

= 1.0.0 =
* Initial release
