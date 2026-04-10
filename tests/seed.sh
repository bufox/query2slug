#!/usr/bin/env bash
#
# Seed script: creates test content for Query2Slug development.
# Demonstrates both editorial (blog) and e-commerce (WooCommerce) use cases.
# Usage: make seed
#

set -euo pipefail

WP="npx @wordpress/env run cli wp"

# =========================================================================
# EDITORIAL CONTENT (standard WordPress posts, categories, tags)
# Use case: a blog/magazine with campaign landing pages
# =========================================================================

echo ""
echo "========================================="
echo "  EDITORIAL CONTENT (Blog)"
echo "========================================="

echo "=== Creating blog categories ==="
$WP term create category "Travel" --slug=travel 2>/dev/null || echo "  travel already exists"
$WP term create category "Food & Recipes" --slug=food-recipes 2>/dev/null || echo "  food-recipes already exists"
$WP term create category "Lifestyle" --slug=lifestyle 2>/dev/null || echo "  lifestyle already exists"

echo "=== Creating blog tags ==="
$WP term create post_tag "Italy" --slug=italy 2>/dev/null || echo "  italy already exists"
$WP term create post_tag "Japan" --slug=japan 2>/dev/null || echo "  japan already exists"
$WP term create post_tag "Budget" --slug=budget 2>/dev/null || echo "  budget already exists"
$WP term create post_tag "Vegan" --slug=vegan 2>/dev/null || echo "  vegan already exists"
$WP term create post_tag "Summer Guide" --slug=summer-guide 2>/dev/null || echo "  summer-guide already exists"
$WP term create post_tag "Editor Pick" --slug=editor-pick 2>/dev/null || echo "  editor-pick already exists"

echo "=== Creating blog posts ==="
$WP post create --post_title="10 Hidden Gems in Tuscany" --post_status=publish --post_category=travel --tags_input=italy,editor-pick 2>/dev/null || true
$WP post create --post_title="Rome on a Budget: A Complete Guide" --post_status=publish --post_category=travel --tags_input=italy,budget 2>/dev/null || true
$WP post create --post_title="Tokyo Street Food: What to Eat" --post_status=publish --post_category=travel,food-recipes --tags_input=japan 2>/dev/null || true
$WP post create --post_title="Best Vegan Restaurants in Milan" --post_status=publish --post_category=food-recipes --tags_input=italy,vegan 2>/dev/null || true
$WP post create --post_title="Summer 2026: Top Beach Destinations" --post_status=publish --post_category=travel --tags_input=summer-guide 2>/dev/null || true
$WP post create --post_title="Easy Vegan Pasta Recipes" --post_status=publish --post_category=food-recipes --tags_input=vegan,editor-pick 2>/dev/null || true
$WP post create --post_title="Budget Travel: 5 Tips That Actually Work" --post_status=publish --post_category=travel,lifestyle --tags_input=budget 2>/dev/null || true
$WP post create --post_title="Japanese Home Cooking for Beginners" --post_status=publish --post_category=food-recipes --tags_input=japan 2>/dev/null || true

# =========================================================================
# E-COMMERCE CONTENT (WooCommerce products, product_cat, product_tag)
# Use case: an online store with campaign landing pages for Google Ads
# =========================================================================

echo ""
echo "========================================="
echo "  E-COMMERCE CONTENT (WooCommerce)"
echo "========================================="

if $WP plugin is-active woocommerce 2>/dev/null || $WP plugin is-active woocommerce.latest-stable 2>/dev/null; then
    echo "  WooCommerce detected, creating products..."

    echo "=== Creating product categories ==="
    $WP term create product_cat "Graphic Tees" --slug=graphic-tees 2>/dev/null || echo "  graphic-tees already exists"
    $WP term create product_cat "Sneakers" --slug=sneakers 2>/dev/null || echo "  sneakers already exists"
    $WP term create product_cat "Bags & Backpacks" --slug=bags 2>/dev/null || echo "  bags already exists"

    echo "=== Creating product tags ==="
    $WP term create product_tag "Disney" --slug=disney 2>/dev/null || echo "  disney already exists"
    $WP term create product_tag "Marvel" --slug=marvel 2>/dev/null || echo "  marvel already exists"
    $WP term create product_tag "New Arrival" --slug=new-arrival 2>/dev/null || echo "  new-arrival already exists"
    $WP term create product_tag "Sale" --slug=sale 2>/dev/null || echo "  sale already exists"
    $WP term create product_tag "Kids" --slug=kids 2>/dev/null || echo "  kids already exists"
    $WP term create product_tag "Limited Edition" --slug=limited-edition 2>/dev/null || echo "  limited-edition already exists"

    echo "=== Creating products ==="
    $WP wc product create --name="Mickey Mouse Vintage Tee" --type=simple --regular_price=29.99 --categories='[{"slug":"graphic-tees"}]' --tags='[{"slug":"disney"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Spider-Man Action Tee" --type=simple --regular_price=24.99 --categories='[{"slug":"graphic-tees"}]' --tags='[{"slug":"marvel"},{"slug":"new-arrival"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Frozen Kids Tee" --type=simple --regular_price=19.99 --sale_price=14.99 --categories='[{"slug":"graphic-tees"}]' --tags='[{"slug":"disney"},{"slug":"kids"},{"slug":"sale"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Iron Man Limited Tee" --type=simple --regular_price=39.99 --categories='[{"slug":"graphic-tees"}]' --tags='[{"slug":"marvel"},{"slug":"limited-edition"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Avengers High-Top Sneakers" --type=simple --regular_price=89.99 --categories='[{"slug":"sneakers"}]' --tags='[{"slug":"marvel"},{"slug":"new-arrival"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Disney x Vans Slip-On" --type=simple --regular_price=74.99 --sale_price=59.99 --categories='[{"slug":"sneakers"}]' --tags='[{"slug":"disney"},{"slug":"sale"},{"slug":"limited-edition"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Spider-Man Kids Sneakers" --type=simple --regular_price=49.99 --categories='[{"slug":"sneakers"}]' --tags='[{"slug":"marvel"},{"slug":"kids"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Marvel Kids Backpack" --type=simple --regular_price=34.99 --categories='[{"slug":"bags"}]' --tags='[{"slug":"marvel"},{"slug":"kids"}]' --user=1 2>/dev/null || true
    $WP wc product create --name="Mickey Mouse Tote Bag" --type=simple --regular_price=22.99 --categories='[{"slug":"bags"}]' --tags='[{"slug":"disney"}]' --user=1 2>/dev/null || true

    echo "  Products created."
else
    echo "  WooCommerce not active, skipping product creation."
fi

# =========================================================================
# PERMALINKS
# =========================================================================

echo ""
echo "=== Flushing permalinks ==="
$WP rewrite structure '/%postname%/' --hard
$WP rewrite flush

# =========================================================================
# QUERY2SLUG RULES
# =========================================================================

echo ""
echo "========================================="
echo "  QUERY2SLUG RULES"
echo "========================================="

$WP eval '
if (!class_exists("Q2S_DB")) { exit; }

// --- Editorial rules (blog posts, WP categories + tags) ---

// Travel + Italy landing page (e.g. for a "Visit Italy" ad campaign)
Q2S_DB::save_rule(["slug" => "travel-italy", "filters" => ["category_name" => "travel", "tag" => "italy"], "status" => 1]);

// Budget travel content hub
Q2S_DB::save_rule(["slug" => "budget-travel-tips", "filters" => ["category_name" => "travel", "tag" => "budget"], "status" => 1]);

// Vegan food editorial collection
Q2S_DB::save_rule(["slug" => "vegan-food-guide", "filters" => ["category_name" => "food-recipes", "tag" => "vegan"], "status" => 1]);

// Summer campaign (seasonal content)
Q2S_DB::save_rule(["slug" => "summer-2026", "filters" => ["tag" => "summer-guide"], "status" => 1]);

// Editor picks (curated content)
Q2S_DB::save_rule(["slug" => "editor-picks", "filters" => ["tag" => "editor-pick"], "status" => 1]);

// Inactive editorial rule
Q2S_DB::save_rule(["slug" => "japan-travel", "filters" => ["category_name" => "travel", "tag" => "japan"], "status" => 0]);

// --- WooCommerce rules (products, product_cat + product_tag) ---
if (taxonomy_exists("product_cat")) {

    // Google Ads: Disney Graphic Tees campaign
    Q2S_DB::save_rule(["slug" => "disney-graphic-tees", "filters" => ["product_cat" => "graphic-tees", "product_tag" => "disney"], "status" => 1]);

    // Meta Ads: Sale items for Disney fans
    Q2S_DB::save_rule(["slug" => "disney-sale", "filters" => ["product_tag" => "disney,sale"], "status" => 1]);

    // Google Ads: New Marvel arrivals
    Q2S_DB::save_rule(["slug" => "new-marvel-gear", "filters" => ["product_tag" => "marvel,new-arrival"], "status" => 1]);

    // Kids shop landing page
    Q2S_DB::save_rule(["slug" => "kids-shop", "filters" => ["product_tag" => "kids"], "status" => 1]);

    // Limited edition drops
    Q2S_DB::save_rule(["slug" => "limited-edition", "filters" => ["product_tag" => "limited-edition"], "status" => 1]);

    echo "WooCommerce rules created.\n";
}

echo "Editorial rules created.\n";
'

echo ""
echo "========================================="
echo "  ALL DONE!"
echo "========================================="
echo ""
echo "  EDITORIAL RULES (blog → WP categories/tags):"
echo "  /lp/travel-italy/               → Travel posts about Italy"
echo "  /lp/budget-travel-tips/         → Budget travel articles"
echo "  /lp/vegan-food-guide/           → Vegan food & recipes"
echo "  /lp/summer-2026/               → Summer guide content"
echo "  /lp/editor-picks/              → Editor-picked articles"
echo "  /lp/japan-travel/              → Japan travel (INACTIVE)"
echo ""
echo "  WOOCOMMERCE RULES (products → product_cat/product_tag):"
echo "  /lp/disney-graphic-tees/        → Graphic Tees by Disney"
echo "  /lp/disney-sale/                → Disney products on sale"
echo "  /lp/new-marvel-gear/            → New Marvel arrivals"
echo "  /lp/kids-shop/                  → All products for kids"
echo "  /lp/limited-edition/            → Limited edition drops"
echo ""
echo "  REDIRECT TESTS:"
echo "  /?category_name=travel&tag=italy                  → 301 → /lp/travel-italy/"
echo "  /?tag=italy&category_name=travel                  → same (order-independent)"
echo "  /?tag=italy&category_name=travel&utm_source=google → with UTM preserved"
echo "  /?product_cat=graphic-tees&product_tag=disney      → 301 → /lp/disney-graphic-tees/"
echo "  /?category_name=travel&tag=japan                   → no redirect (INACTIVE)"
