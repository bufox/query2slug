<?php
/**
 * MU-Plugin: Demo shop filter panel.
 *
 * Adds a "Shop Filters" widget with dropdowns for product category and
 * product tag. Submits as GET query strings — exactly the kind of URL
 * that Query2Slug turns into a clean slug.
 *
 * Install: copy to wp-content/mu-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Q2S_Demo_Filter_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'q2s_demo_filters',
			'Shop Filters',
			array( 'description' => 'Filter by category + tag (demo)' )
		);
	}

	public function widget( $args, $instance ) {
		if ( ! is_post_type_archive( 'product' ) && ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		$cats = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => array( get_option( 'default_product_cat', 0 ) ),
		) );

		$tags = get_terms( array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
		) );

		if ( empty( $cats ) && empty( $tags ) ) {
			return;
		}

		$current_cat = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '';
		$current_tag = isset( $_GET['product_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['product_tag'] ) ) : '';

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $instance['title'] ?? 'Shop Filters' ) . $args['after_title'];
		?>
		<form method="get" action="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="q2s-demo-filters">
			<p>
				<label for="q2s-df-cat"><strong>Category</strong></label><br>
				<select name="product_cat" id="q2s-df-cat" style="width:100%">
					<option value="">— Any —</option>
					<?php foreach ( $cats as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat->slug ); ?>"
							<?php selected( $current_cat, $cat->slug ); ?>>
							<?php echo esc_html( $cat->name ); ?> (<?php echo (int) $cat->count; ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="q2s-df-tag"><strong>Tag</strong></label><br>
				<select name="product_tag" id="q2s-df-tag" style="width:100%">
					<option value="">— Any —</option>
					<?php foreach ( $tags as $tag ) : ?>
						<option value="<?php echo esc_attr( $tag->slug ); ?>"
							<?php selected( $current_tag, $tag->slug ); ?>>
							<?php echo esc_html( $tag->name ); ?> (<?php echo (int) $tag->count; ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<button type="submit" class="button" style="width:100%">Filter Products</button>
			</p>
		</form>
		<?php
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? 'Shop Filters';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array( 'title' => sanitize_text_field( $new_instance['title'] ?? '' ) );
	}
}

add_action( 'widgets_init', function () {
	register_widget( 'Q2S_Demo_Filter_Widget' );
} );
