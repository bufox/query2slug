<?php
/**
 * Demo seed script for Q2S screenshots/GIFs.
 * Run with: npx @wordpress/env run cli wp eval-file /var/www/html/wp-content/plugins/wp-query2slug/tests/seed-demo.php
 */

// Safety check.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== Q2S Demo Seed ===\n\n";

// ─── 1. Clean up duplicates ───
echo "--- Cleaning duplicates ---\n";
$posts = get_posts( array(
	'post_type'      => array( 'post', 'product' ),
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'orderby'        => 'ID',
	'order'          => 'ASC',
) );

$seen_titles = array();
$deleted     = 0;
foreach ( $posts as $pid ) {
	$title = get_the_title( $pid );
	if ( isset( $seen_titles[ $title ] ) ) {
		wp_delete_post( $pid, true );
		$deleted++;
	} else {
		$seen_titles[ $title ] = $pid;
	}
}
echo "Deleted $deleted duplicate posts.\n";

// ─── 2. Post tags ───
echo "\n--- Creating post tags ---\n";
$post_tags = array(
	'italy'       => 'Italy',
	'japan'       => 'Japan',
	'budget'      => 'Budget',
	'vegan'       => 'Vegan',
	'summer-guide' => 'Summer Guide',
	'editor-pick' => "Editor's Pick",
	'street-food' => 'Street Food',
	'guide'       => 'Guide',
	'recipe'      => 'Recipe',
	'cooking'     => 'Cooking',
);
foreach ( $post_tags as $slug => $name ) {
	if ( ! term_exists( $slug, 'post_tag' ) ) {
		wp_insert_term( $name, 'post_tag', array( 'slug' => $slug ) );
		echo "  Created tag: $name\n";
	}
}

// ─── 3. Assign tags to existing posts ───
echo "\n--- Assigning tags to posts ---\n";
$tag_map = array(
	'10 Hidden Gems in Tuscany'               => array( 'italy', 'guide', 'editor-pick' ),
	'Rome on a Budget: A Complete Guide'       => array( 'italy', 'budget', 'guide' ),
	'Tokyo Street Food: What to Eat'           => array( 'japan', 'street-food', 'guide' ),
	'Best Vegan Restaurants in Milan'          => array( 'italy', 'vegan' ),
	'Summer 2026: Top Beach Destinations'      => array( 'summer-guide', 'editor-pick' ),
	'Easy Vegan Pasta Recipes'                 => array( 'vegan', 'recipe', 'italy' ),
	'Budget Travel: 5 Tips That Actually Work' => array( 'budget', 'guide' ),
	'Japanese Home Cooking for Beginners'      => array( 'japan', 'cooking', 'recipe' ),
);
foreach ( $tag_map as $title => $tags ) {
	$post = get_page_by_title( $title, OBJECT, 'post' );
	if ( $post ) {
		wp_set_post_tags( $post->ID, $tags );
		echo "  Tagged: $title\n";
	}
}

// ─── 4. More blog posts ───
echo "\n--- Creating additional blog posts ---\n";
$new_posts = array(
	array(
		'title'    => 'Weekend in Florence: Art, Food & Wine',
		'category' => array( 'travel', 'food-recipes' ),
		'tags'     => array( 'italy', 'guide', 'editor-pick' ),
	),
	array(
		'title'    => 'Osaka vs Tokyo: Which to Visit First?',
		'category' => array( 'travel' ),
		'tags'     => array( 'japan', 'guide' ),
	),
	array(
		'title'    => '15-Minute Vegan Lunch Ideas',
		'category' => array( 'food-recipes' ),
		'tags'     => array( 'vegan', 'recipe' ),
	),
	array(
		'title'    => 'Digital Nomad Guide to Southeast Asia',
		'category' => array( 'travel', 'lifestyle' ),
		'tags'     => array( 'budget', 'guide' ),
	),
	array(
		'title'    => 'Plant-Based Mediterranean Diet 101',
		'category' => array( 'food-recipes', 'lifestyle' ),
		'tags'     => array( 'vegan', 'guide' ),
	),
);
foreach ( $new_posts as $p ) {
	if ( get_page_by_title( $p['title'], OBJECT, 'post' ) ) {
		echo "  Skipped (exists): {$p['title']}\n";
		continue;
	}
	$id = wp_insert_post( array(
		'post_title'   => $p['title'],
		'post_content' => "<!-- wp:paragraph --><p>This is demo content for {$p['title']}.</p><!-- /wp:paragraph -->",
		'post_status'  => 'publish',
		'post_type'    => 'post',
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		wp_set_object_terms( $id, $p['category'], 'category' );
		wp_set_post_tags( $id, $p['tags'] );
		echo "  Created: {$p['title']}\n";
	}
}

// ─── 5. WooCommerce product categories ───
echo "\n--- Setting up WooCommerce categories ---\n";
$woo_cats = array(
	'clothing'     => 'Clothing',
	'tshirts'      => array( 'name' => 'T-Shirts', 'parent' => 'clothing' ),
	'hoodies'      => array( 'name' => 'Hoodies', 'parent' => 'clothing' ),
	'sneakers'     => 'Sneakers',
	'bags'         => 'Bags & Backpacks',
	'accessories'  => 'Accessories',
	'hats'         => array( 'name' => 'Hats', 'parent' => 'accessories' ),
	'socks'        => array( 'name' => 'Socks', 'parent' => 'accessories' ),
	'new-arrivals' => 'New Arrivals',
	'sale'         => 'Sale',
);
$cat_ids = array();
foreach ( $woo_cats as $slug => $data ) {
	$name   = is_array( $data ) ? $data['name'] : $data;
	$parent = is_array( $data ) && isset( $data['parent'] ) ? ( $cat_ids[ $data['parent'] ] ?? 0 ) : 0;
	$term   = term_exists( $slug, 'product_cat' );
	if ( $term ) {
		$cat_ids[ $slug ] = (int) $term['term_id'];
		echo "  Exists: $name\n";
	} else {
		$result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
		if ( ! is_wp_error( $result ) ) {
			$cat_ids[ $slug ] = $result['term_id'];
			echo "  Created: $name\n";
		}
	}
}

// ─── 6. WooCommerce product tags ───
echo "\n--- Setting up WooCommerce product tags ---\n";
$woo_tags = array(
	'disney'          => 'Disney',
	'marvel'          => 'Marvel',
	'star-wars'       => 'Star Wars',
	'anime'           => 'Anime',
	'retro'           => 'Retro',
	'limited-edition' => 'Limited Edition',
	'new-arrival'     => 'New Arrival',
	'best-seller'     => 'Best Seller',
	'eco-friendly'    => 'Eco-Friendly',
	'kids'            => 'Kids',
	'unisex'          => 'Unisex',
	'sale'            => 'Sale',
	'summer'          => 'Summer',
	'winter'          => 'Winter',
);
foreach ( $woo_tags as $slug => $name ) {
	if ( ! term_exists( $slug, 'product_tag' ) ) {
		wp_insert_term( $name, 'product_tag', array( 'slug' => $slug ) );
		echo "  Created: $name\n";
	}
}

// ─── 7. Products ───
echo "\n--- Creating products ---\n";
$products = array(
	// T-Shirts
	array(
		'name'  => 'Mickey Classic Vintage Tee',
		'price' => '29.99',
		'sale'  => '24.99',
		'cats'  => array( 'tshirts', 'clothing', 'sale' ),
		'tags'  => array( 'disney', 'retro', 'unisex', 'sale', 'best-seller' ),
		'sku'   => 'TSH-DIS-001',
	),
	array(
		'name'  => 'Spider-Man Web Sling Tee',
		'price' => '34.99',
		'cats'  => array( 'tshirts', 'clothing' ),
		'tags'  => array( 'marvel', 'best-seller', 'unisex' ),
		'sku'   => 'TSH-MRV-001',
	),
	array(
		'name'  => 'Darth Vader Dark Side Tee',
		'price' => '32.99',
		'cats'  => array( 'tshirts', 'clothing' ),
		'tags'  => array( 'star-wars', 'unisex' ),
		'sku'   => 'TSH-SW-001',
	),
	array(
		'name'  => 'Naruto Ramen Graphic Tee',
		'price' => '27.99',
		'cats'  => array( 'tshirts', 'clothing', 'new-arrivals' ),
		'tags'  => array( 'anime', 'new-arrival', 'unisex' ),
		'sku'   => 'TSH-ANI-001',
	),
	array(
		'name'  => 'Iron Man Arc Reactor Tee',
		'price' => '34.99',
		'sale'  => '27.99',
		'cats'  => array( 'tshirts', 'clothing', 'sale' ),
		'tags'  => array( 'marvel', 'sale' ),
		'sku'   => 'TSH-MRV-002',
	),
	array(
		'name'  => 'Retro Pac-Man Pixel Tee',
		'price' => '25.99',
		'cats'  => array( 'tshirts', 'clothing' ),
		'tags'  => array( 'retro', 'unisex', 'best-seller' ),
		'sku'   => 'TSH-RET-001',
	),
	array(
		'name'  => 'Baby Yoda Cute Force Tee',
		'price' => '29.99',
		'cats'  => array( 'tshirts', 'clothing', 'new-arrivals' ),
		'tags'  => array( 'star-wars', 'new-arrival', 'kids' ),
		'sku'   => 'TSH-SW-002',
	),
	array(
		'name'  => 'Dragon Ball Z Power Up Tee',
		'price' => '27.99',
		'cats'  => array( 'tshirts', 'clothing' ),
		'tags'  => array( 'anime', 'unisex' ),
		'sku'   => 'TSH-ANI-002',
	),
	// Hoodies
	array(
		'name'  => 'Marvel Avengers Logo Hoodie',
		'price' => '59.99',
		'cats'  => array( 'hoodies', 'clothing' ),
		'tags'  => array( 'marvel', 'unisex', 'winter', 'best-seller' ),
		'sku'   => 'HOO-MRV-001',
	),
	array(
		'name'  => 'Disney Castle Dreams Hoodie',
		'price' => '54.99',
		'sale'  => '44.99',
		'cats'  => array( 'hoodies', 'clothing', 'sale' ),
		'tags'  => array( 'disney', 'sale', 'winter' ),
		'sku'   => 'HOO-DIS-001',
	),
	array(
		'name'  => 'Star Wars Rebel Alliance Hoodie',
		'price' => '64.99',
		'cats'  => array( 'hoodies', 'clothing', 'new-arrivals' ),
		'tags'  => array( 'star-wars', 'new-arrival', 'limited-edition' ),
		'sku'   => 'HOO-SW-001',
	),
	array(
		'name'  => 'Attack on Titan Survey Corps Hoodie',
		'price' => '57.99',
		'cats'  => array( 'hoodies', 'clothing' ),
		'tags'  => array( 'anime', 'unisex' ),
		'sku'   => 'HOO-ANI-001',
	),
	// Sneakers
	array(
		'name'  => 'Mickey Mouse Retro Sneakers',
		'price' => '89.99',
		'cats'  => array( 'sneakers' ),
		'tags'  => array( 'disney', 'retro', 'limited-edition' ),
		'sku'   => 'SNK-DIS-001',
	),
	array(
		'name'  => 'Spider-Verse High Tops',
		'price' => '99.99',
		'cats'  => array( 'sneakers', 'new-arrivals' ),
		'tags'  => array( 'marvel', 'new-arrival', 'limited-edition' ),
		'sku'   => 'SNK-MRV-001',
	),
	array(
		'name'  => 'Eco Runner Bamboo Sneakers',
		'price' => '79.99',
		'cats'  => array( 'sneakers' ),
		'tags'  => array( 'eco-friendly', 'unisex', 'best-seller' ),
		'sku'   => 'SNK-ECO-001',
	),
	array(
		'name'  => 'Retro Wave Classic Sneakers',
		'price' => '74.99',
		'sale'  => '59.99',
		'cats'  => array( 'sneakers', 'sale' ),
		'tags'  => array( 'retro', 'sale', 'unisex' ),
		'sku'   => 'SNK-RET-001',
	),
	// Bags
	array(
		'name'  => 'Marvel Shield Backpack',
		'price' => '49.99',
		'cats'  => array( 'bags' ),
		'tags'  => array( 'marvel', 'unisex' ),
		'sku'   => 'BAG-MRV-001',
	),
	array(
		'name'  => 'Disney Princess Mini Backpack',
		'price' => '39.99',
		'cats'  => array( 'bags' ),
		'tags'  => array( 'disney', 'kids' ),
		'sku'   => 'BAG-DIS-001',
	),
	array(
		'name'  => 'Eco Canvas Tote Bag',
		'price' => '24.99',
		'cats'  => array( 'bags' ),
		'tags'  => array( 'eco-friendly', 'unisex', 'summer' ),
		'sku'   => 'BAG-ECO-001',
	),
	array(
		'name'  => 'Star Wars Imperial Messenger Bag',
		'price' => '44.99',
		'sale'  => '34.99',
		'cats'  => array( 'bags', 'sale' ),
		'tags'  => array( 'star-wars', 'sale' ),
		'sku'   => 'BAG-SW-001',
	),
	// Accessories - Hats
	array(
		'name'  => 'Marvel Snapback Cap',
		'price' => '22.99',
		'cats'  => array( 'hats', 'accessories' ),
		'tags'  => array( 'marvel', 'unisex', 'summer' ),
		'sku'   => 'HAT-MRV-001',
	),
	array(
		'name'  => 'Disney Ears Beanie',
		'price' => '19.99',
		'cats'  => array( 'hats', 'accessories', 'new-arrivals' ),
		'tags'  => array( 'disney', 'winter', 'new-arrival', 'kids' ),
		'sku'   => 'HAT-DIS-001',
	),
	array(
		'name'  => 'Anime Cat Ears Bucket Hat',
		'price' => '18.99',
		'cats'  => array( 'hats', 'accessories' ),
		'tags'  => array( 'anime', 'summer', 'unisex' ),
		'sku'   => 'HAT-ANI-001',
	),
	// Accessories - Socks
	array(
		'name'  => 'Marvel Heroes Sock Pack (3 pairs)',
		'price' => '14.99',
		'cats'  => array( 'socks', 'accessories' ),
		'tags'  => array( 'marvel', 'best-seller' ),
		'sku'   => 'SOC-MRV-001',
	),
	array(
		'name'  => 'Star Wars Droid Socks',
		'price' => '9.99',
		'cats'  => array( 'socks', 'accessories' ),
		'tags'  => array( 'star-wars', 'kids' ),
		'sku'   => 'SOC-SW-001',
	),
	array(
		'name'  => 'Retro Pixel Art Sock Pack',
		'price' => '12.99',
		'sale'  => '9.99',
		'cats'  => array( 'socks', 'accessories', 'sale' ),
		'tags'  => array( 'retro', 'sale', 'unisex' ),
		'sku'   => 'SOC-RET-001',
	),
);

foreach ( $products as $p ) {
	// Skip if product already exists.
	$existing = get_page_by_title( $p['name'], OBJECT, 'product' );
	if ( $existing ) {
		echo "  Skipped (exists): {$p['name']}\n";
		continue;
	}

	$id = wp_insert_post( array(
		'post_title'   => $p['name'],
		'post_content' => '',
		'post_status'  => 'publish',
		'post_type'    => 'product',
	) );

	if ( ! $id || is_wp_error( $id ) ) {
		echo "  FAILED: {$p['name']}\n";
		continue;
	}

	// Set product type and price.
	wp_set_object_terms( $id, 'simple', 'product_type' );
	update_post_meta( $id, '_regular_price', $p['price'] );
	update_post_meta( $id, '_sku', $p['sku'] );

	if ( isset( $p['sale'] ) ) {
		update_post_meta( $id, '_sale_price', $p['sale'] );
		update_post_meta( $id, '_price', $p['sale'] );
	} else {
		update_post_meta( $id, '_price', $p['price'] );
	}

	update_post_meta( $id, '_stock_status', 'instock' );
	update_post_meta( $id, '_manage_stock', 'no' );
	update_post_meta( $id, '_visibility', 'visible' );

	// Categories.
	$term_ids = array();
	foreach ( $p['cats'] as $cat_slug ) {
		if ( isset( $cat_ids[ $cat_slug ] ) ) {
			$term_ids[] = $cat_ids[ $cat_slug ];
		}
	}
	if ( $term_ids ) {
		wp_set_object_terms( $id, $term_ids, 'product_cat' );
	}

	// Tags.
	$tag_names = array();
	foreach ( $p['tags'] as $tag_slug ) {
		if ( isset( $woo_tags[ $tag_slug ] ) ) {
			$tag_names[] = $woo_tags[ $tag_slug ];
		}
	}
	if ( $tag_names ) {
		wp_set_object_terms( $id, $tag_names, 'product_tag' );
	}

	echo "  Created: {$p['name']} (\${$p['price']})\n";
}

// ─── 8. Reassign existing uncategorized products ───
echo "\n--- Fixing uncategorized products ---\n";
$uncat_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
if ( $uncat_term ) {
	$uncat_products = get_posts( array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $uncat_term->term_id,
				'operator' => 'IN',
			),
		),
	) );
	// Remove "uncategorized" from products that now have other categories.
	foreach ( $uncat_products as $pid ) {
		$cats = wp_get_object_terms( $pid, 'product_cat', array( 'fields' => 'slugs' ) );
		if ( count( $cats ) > 1 && in_array( 'uncategorized', $cats, true ) ) {
			wp_remove_object_terms( $pid, $uncat_term->term_id, 'product_cat' );
			echo "  Removed 'uncategorized' from: " . get_the_title( $pid ) . "\n";
		}
	}
}

// ─── 9. Clean existing Q2S rules and recreate ───
echo "\n--- Setting up Q2S rules ---\n";
global $wpdb;
$table = $wpdb->prefix . 'q2s_rules';
$wpdb->query( "TRUNCATE TABLE {$table}" );
echo "  Cleared existing rules.\n";

$rules = array(
	// Blog rules - clearly useful use cases.
	array(
		'slug'    => 'travel-italy',
		'filters' => array( 'category_name' => 'travel', 'tag' => 'italy' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'japan-travel',
		'filters' => array( 'category_name' => 'travel', 'tag' => 'japan' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'vegan-recipes',
		'filters' => array( 'category_name' => 'food-recipes', 'tag' => 'vegan' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'budget-travel',
		'filters' => array( 'category_name' => 'travel', 'tag' => 'budget' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'editor-picks',
		'filters' => array( 'tag' => 'editor-pick' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'summer-guide-2026',
		'filters' => array( 'tag' => 'summer-guide' ),
		'status'  => 0, // Inactive — nice for showing toggle feature.
	),
	// WooCommerce rules - showcase e-commerce use case.
	array(
		'slug'    => 'marvel-collection',
		'filters' => array( 'product_tag' => 'marvel' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'disney-shop',
		'filters' => array( 'product_tag' => 'disney' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'sneakers-sale',
		'filters' => array( 'product_cat' => 'sneakers', 'product_tag' => 'sale' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'new-arrivals',
		'filters' => array( 'product_cat' => 'new-arrivals' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'eco-friendly',
		'filters' => array( 'product_tag' => 'eco-friendly' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'limited-drops',
		'filters' => array( 'product_tag' => 'limited-edition' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'kids-shop',
		'filters' => array( 'product_tag' => 'kids' ),
		'status'  => 1,
	),
	array(
		'slug'    => 'anime-merch',
		'filters' => array( 'product_tag' => 'anime' ),
		'status'  => 0, // Another inactive one.
	),
);

foreach ( $rules as $rule ) {
	ksort( $rule['filters'] );
	$filter_hash = hash( 'sha256', wp_json_encode( $rule['filters'] ) );
	$wpdb->insert(
		$table,
		array(
			'slug'        => $rule['slug'],
			'filters'     => wp_json_encode( $rule['filters'] ),
			'filter_hash' => $filter_hash,
			'status'      => $rule['status'],
		),
		array( '%s', '%s', '%s', '%d' )
	);
	$status_label = $rule['status'] ? 'active' : 'inactive';
	echo "  Rule: /lp/{$rule['slug']} ($status_label)\n";
}

// ─── 10. Site settings ───
echo "\n--- Configuring site ---\n";
update_option( 'blogname', 'GeekStyle Shop' );
update_option( 'blogdescription', 'Pop Culture Apparel & Accessories' );
echo "  Site title: GeekStyle Shop\n";

// Flush rewrite rules.
set_transient( 'q2s_flush_rewrite', true );
flush_rewrite_rules();
echo "  Flushed rewrite rules.\n";

echo "\n=== Seed complete! ===\n";
echo "Rules:    " . $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) . "\n";
echo "Posts:    " . wp_count_posts()->publish . "\n";
echo "Products: " . wp_count_posts( 'product' )->publish . "\n";
