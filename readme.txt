=== Query2Slug ===
Contributors: simonemorobufox
Tags: url, slug, landing-page, woocommerce, campaign
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Map query string parameters to clean, canonical URL slugs. Create campaign-ready landing URLs without creating pages.

== Description ==

Query2Slug lets you turn filtered WordPress pages into clean, canonical URLs — perfect for ad campaigns (Google Ads, Meta), SEO landing pages, and shareable links.

Instead of sending users to ugly URLs like:

`/shop/?product_cat=t-shirt&product_tag=disney`

You create a clean slug that resolves to the same filtered view:

`/lp/t-shirt-disney/`

**No pages to create. No content to duplicate. Just a simple mapping.**

= How it works =

1. Set a URL prefix (default: `lp`)
2. Create rules that map a slug to a set of query parameters
3. The plugin registers rewrite rules and handles canonical redirects automatically

= Key features =

* **Clean campaign URLs** — create pretty URLs for any combination of query parameters
* **Canonical redirect** — visitors arriving via query strings are automatically redirected (301) to the clean URL
* **UTM preservation** — tracking parameters (utm_source, fbclid, gclid, etc.) are preserved through redirects
* **WooCommerce-friendly** — works with product categories, tags, and attributes out of the box
* **Works everywhere** — not limited to WooCommerce; supports any WordPress taxonomy or query var
* **Autocomplete** — filter editor suggests registered taxonomies and their terms
* **Zero frontend impact** — no CSS or JavaScript added to your site's frontend

= Use cases =

* Google Ads / Meta campaign landing pages
* SEO-friendly filtered archive pages
* Clean shareable links for filtered product collections
* Any scenario where you need a pretty URL for a query string

== Installation ==

1. Upload the `query2slug` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Go to **Query2Slug > Settings** to configure your URL prefix
4. Go to **Query2Slug > Add Rule** to create your first mapping

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

No. Query2Slug works with any WordPress site. WooCommerce taxonomies and attributes will appear in the autocomplete when WooCommerce is active, but the plugin functions independently.

= What happens if I deactivate the plugin? =

All your rules are preserved in the database. The clean URLs will simply stop working (return 404) until you reactivate. No content is lost.

= Can I use this with custom taxonomies? =

Yes. Any registered public taxonomy will appear in the filter key autocomplete.

= How does the canonical redirect work? =

If a visitor arrives at a URL with query parameters that exactly match a rule (e.g., `?product_cat=t-shirt&product_tag=disney`), the plugin issues a 301 redirect to the clean URL. UTM and tracking parameters are preserved.

== Screenshots ==

1. Rules list with status toggle, URL preview, and filter summary
2. Rule editor with slug preview and filter autocomplete
3. Settings page with URL prefix configuration and overview widget

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
