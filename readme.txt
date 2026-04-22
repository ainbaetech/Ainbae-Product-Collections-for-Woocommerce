=== Ainbae Product Collections for WooCommerce ===
Contributors:       ainbae
Tags:               woocommerce, collections, product taxonomy, product categories
Requires at least:  5.8
Tested up to:       6.9
Requires PHP:       7.4
Stable tag:         1.0.0
WC requires at least: 6.0
WC tested up to: 10.7
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.ainbae.com/donate


Adds a "Collections" taxonomy to WooCommerce — identical to Product Categories with full hierarchy, admin panel, and frontend archive support.

== Description ==

**Ainbae Product Collections for WooCommerce** extends WooCommerce with a new "Collections" taxonomy that works exactly like Product Categories.

= Features =

* **Products → Collections** admin page — manage collections just like categories
* **Hierarchical** — supports parent/child collections (e.g. Seasonal → Summer 2024)
* **Product edit screen** — Collections meta box on the sidebar (identical UX to Categories — checkbox list + Most Used tab + inline Add New form)
* **Collections column** in the Products list table with filter dropdown
* **Public archive pages** at `/collection/summer/` — uses WooCommerce's full product loop, sorting, pagination, and filters
* **WooCommerce breadcrumb** integration including ancestor trail
* **Page title & description** pulled from the collection term
* **REST API / Block editor** support
* **HPOS compatible** (High-Performance Order Storage)
* Follows WooCommerce capability conventions (`manage_product_terms`, `edit_product_terms`, etc.)

= Theme Compatibility =

The plugin automatically uses WooCommerce's `archive-product.php` template.
To customise the collection archive layout, add any of these to your theme:

* `taxonomy-product_collection-{slug}.php`
* `taxonomy-product_collection.php`
* `woocommerce/taxonomy-product_collection.php`

== Installation ==

**Automatic installation (recommended)**
1. In your WordPress admin, go to **Plugins → Add New**.
2. Search for *Ainbae Product Collections for WooCommerce*.
3. Click **Install Now**, then **Activate**.
4. Go to **Products → Collections** to create your first collection
5. Assign collections to products from the product edit screen sidebar

**Manual installation**
1. Download the plugin zip file.
2. Go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate the plugin.
4. Go to **Products → Collections** to create your first collection
5. Assign collections to products from the product edit screen sidebar

== Frequently Asked Questions ==

= Does this replace Product Categories? =
No. Collections is a completely separate taxonomy that runs alongside Product Categories.

= Do collection URLs work automatically? =
Yes. On plugin activation rewrite rules are flushed so `/collection/summer/` works immediately.

= Can I nest collections? =
Yes. Full parent/child hierarchy is supported — both in the admin and in the frontend URL structure.

= Is it compatible with WPML / Polylang? =
The taxonomy is registered as translatable-friendly. WPML/Polylang users should register the taxonomy in their plugin settings for string translation.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
