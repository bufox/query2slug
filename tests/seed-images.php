<?php
/**
 * Add featured images to demo posts and products.
 * Run with: npx @wordpress/env run cli wp eval-file /var/www/html/wp-content/plugins/wp-query2slug/tests/seed-images.php
 *
 * Uses picsum.photos (CC0, no auth) with curated IDs for realistic results.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

echo "=== Q2S Demo Images ===\n\n";

// ─── Curated Picsum IDs ───
// Blog posts: landscape 1200x800.
$blog_images = array(
	'10 Hidden Gems in Tuscany'               => 429,  // Tuscany landscape.
	'Rome on a Budget: A Complete Guide'       => 1044, // Architecture.
	'Tokyo Street Food: What to Eat'           => 674,  // Food/Asian.
	'Best Vegan Restaurants in Milan'          => 292,  // Restaurant/food.
	'Summer 2026: Top Beach Destinations'      => 1040, // Beach.
	'Easy Vegan Pasta Recipes'                 => 1080, // Food.
	'Budget Travel: 5 Tips That Actually Work' => 1036, // Travel landscape.
	'Japanese Home Cooking for Beginners'      => 835,  // Cooking.
	'Weekend in Florence: Art, Food & Wine'    => 318,  // Architecture/art.
	'Osaka vs Tokyo: Which to Visit First?'    => 452,  // City.
	'15-Minute Vegan Lunch Ideas'              => 488,  // Food/fresh.
	'Digital Nomad Guide to Southeast Asia'    => 514,  // Tropical.
	'Plant-Based Mediterranean Diet 101'       => 225,  // Food/plants.
);

// Products: square 800x800.
$product_images = array(
	// T-Shirts.
	'Mickey Classic Vintage Tee'            => 572,
	'Spider-Man Web Sling Tee'              => 593,
	'Darth Vader Dark Side Tee'             => 669,
	'Naruto Ramen Graphic Tee'              => 737,
	'Iron Man Arc Reactor Tee'              => 550,
	'Retro Pac-Man Pixel Tee'              => 367,
	'Baby Yoda Cute Force Tee'              => 659,
	'Dragon Ball Z Power Up Tee'            => 620,
	// Hoodies.
	'Marvel Avengers Logo Hoodie'           => 500,
	'Disney Castle Dreams Hoodie'           => 538,
	'Star Wars Rebel Alliance Hoodie'       => 545,
	'Attack on Titan Survey Corps Hoodie'   => 610,
	// Sneakers.
	'Mickey Mouse Retro Sneakers'           => 21,
	'Spider-Verse High Tops'                => 103,
	'Eco Runner Bamboo Sneakers'            => 180,
	'Retro Wave Classic Sneakers'           => 145,
	// Bags.
	'Marvel Shield Backpack'                => 684,
	'Disney Princess Mini Backpack'         => 642,
	'Eco Canvas Tote Bag'                   => 399,
	'Star Wars Imperial Messenger Bag'      => 635,
	// Hats.
	'Marvel Snapback Cap'                   => 164,
	'Disney Ears Beanie'                    => 177,
	'Anime Cat Ears Bucket Hat'             => 237,
	// Socks.
	'Marvel Heroes Sock Pack (3 pairs)'     => 350,
	'Star Wars Droid Socks'                 => 349,
	'Retro Pixel Art Sock Pack'             => 256,
);

/**
 * Download and attach a featured image from picsum.photos.
 */
function q2s_seed_set_image( int $post_id, int $picsum_id, string $size = '1200/800' ): bool {
	// Skip if already has a thumbnail.
	if ( has_post_thumbnail( $post_id ) ) {
		return false;
	}

	$url       = "https://picsum.photos/id/{$picsum_id}/{$size}.jpg";
	$post_title = get_the_title( $post_id );
	$desc       = sanitize_file_name( sanitize_title( $post_title ) );

	$attach_id = media_sideload_image( $url, $post_id, $post_title, 'id' );

	if ( is_wp_error( $attach_id ) ) {
		echo "  ERROR ({$post_title}): " . $attach_id->get_error_message() . "\n";
		return false;
	}

	set_post_thumbnail( $post_id, $attach_id );
	return true;
}

// ─── Blog posts ───
echo "--- Blog post images ---\n";
foreach ( $blog_images as $title => $picsum_id ) {
	$post = get_page_by_title( $title, OBJECT, 'post' );
	if ( ! $post ) {
		echo "  Not found: $title\n";
		continue;
	}
	if ( has_post_thumbnail( $post->ID ) ) {
		echo "  Skipped (has image): $title\n";
		continue;
	}
	$result = q2s_seed_set_image( $post->ID, $picsum_id, '1200/800' );
	if ( $result ) {
		echo "  Set image: $title (picsum #{$picsum_id})\n";
	}
}

// ─── Products ───
echo "\n--- Product images ---\n";
foreach ( $product_images as $title => $picsum_id ) {
	$post = get_page_by_title( $title, OBJECT, 'product' );
	if ( ! $post ) {
		echo "  Not found: $title\n";
		continue;
	}
	if ( has_post_thumbnail( $post->ID ) ) {
		echo "  Skipped (has image): $title\n";
		continue;
	}
	$result = q2s_seed_set_image( $post->ID, $picsum_id, '800/800' );
	if ( $result ) {
		echo "  Set image: $title (picsum #{$picsum_id})\n";
	}
}

echo "\n=== Images done! ===\n";
$total = (int) ( new WP_Query( array(
	'post_type'      => array( 'post', 'product' ),
	'posts_per_page' => -1,
	'meta_key'       => '_thumbnail_id',
	'fields'         => 'ids',
) ) )->found_posts;
echo "Posts/products with featured image: $total\n";
