# Query2Slug

Map query string parameters to clean, canonical URL slugs. Create campaign-ready landing URLs without creating pages.

![Create a rule](assets/demo/create-rule.gif)

## The problem

You're running a Google Ads campaign pointing to a filtered WooCommerce page. The URL looks like this:

```
/shop/?product_cat=t-shirt&product_tag=disney
```

Ugly, hard to share, and terrible for branding. Creating a dedicated page means duplicating content and manually keeping it in sync.

## The solution

Query2Slug lets you create a clean slug that resolves to the same filtered view:

```
/lp/t-shirt-disney/
```

**No pages to create. No content to duplicate. Just a simple mapping.**

Visitors arriving via the ugly query string are automatically redirected (301) to the clean URL — with UTM and tracking parameters preserved.

## Features

- **Clean campaign URLs** — create pretty URLs for any combination of query parameters
- **Canonical redirect** — query string visitors are automatically 301-redirected to the clean URL
- **UTM preservation** — tracking parameters (`utm_source`, `fbclid`, `gclid`, etc.) are preserved through redirects
- **WooCommerce-friendly** — works with product categories, tags, and attributes out of the box
- **Works everywhere** — not limited to WooCommerce; supports any WordPress taxonomy or query var
- **Autocomplete** — filter editor suggests registered taxonomies and their terms
- **Zero frontend impact** — no CSS or JavaScript added to your site's frontend

## How it works

1. Set a URL prefix (default: `lp`)
2. Create rules that map a slug to a set of query parameters
3. The plugin registers rewrite rules and handles canonical redirects automatically

## Use cases

- Google Ads / Meta campaign landing pages
- SEO-friendly filtered archive pages
- Clean shareable links for filtered product collections
- Any scenario where you need a pretty URL for a query string

## Installation

### From WordPress.org (coming soon)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Query2Slug"
3. Click **Install Now**, then **Activate**

### Manual

1. Download the latest release from the [Releases page](https://github.com/bufox/query2slug/releases)
2. Upload the `query2slug` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin

## Quick start

1. Go to **Query2Slug > Settings** to configure your URL prefix
2. Go to **Query2Slug > Add Rule** to create your first mapping
3. Enter a slug and add one or more query parameter filters
4. Save — your clean URL is live immediately

## FAQ

### Does this plugin require WooCommerce?

No. Query2Slug works with any WordPress site. WooCommerce taxonomies and attributes will appear in the autocomplete when WooCommerce is active, but the plugin functions independently.

### What happens if I deactivate the plugin?

All your rules are preserved in the database. The clean URLs will simply stop working (return 404) until you reactivate. No content is lost.

### Can I use this with custom taxonomies?

Yes. Any registered public taxonomy will appear in the filter key autocomplete.

### How does the canonical redirect work?

If a visitor arrives at a URL with query parameters that exactly match a rule (e.g., `?product_cat=t-shirt&product_tag=disney`), the plugin issues a 301 redirect to the clean URL. UTM and tracking parameters are preserved.

## Requirements

- WordPress 6.0+
- PHP 8.0+

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
