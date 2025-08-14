<?php
namespace DomGats\ProductFilter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class DGCPF_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_filter_products_by_tag', [ $this, 'filter_products_handler' ] );
		add_action( 'wp_ajax_nopriv_filter_products_by_tag', [ $this, 'filter_products_handler' ] );
	}

	/**
	 * The main AJAX handler for filtering products.
	 */
	public function filter_products_handler() {
		check_ajax_referer( 'product_filter_nonce', 'nonce' );

		// Sanitize all POST data
		$page_id       = isset( $_POST['page_id'] ) ? intval( $_POST['page_id'] ) : 0;
		$widget_id     = isset( $_POST['widget_id'] ) ? sanitize_text_field( $_POST['widget_id'] ) : '';
		$filter_type   = isset( $_POST['filter_type'] ) ? sanitize_text_field( $_POST['filter_type'] ) : 'products';
		$selected_tags = isset( $_POST['tags'] ) && is_array( $_POST['tags'] ) ? array_map( 'sanitize_text_field', $_POST['tags'] ) : [];
		$page          = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$template_id   = 346; // Default template

		// Logic to find the Elementor template ID
		if ( $page_id && $widget_id && class_exists( '\Elementor\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $page_id );
			if ( $document ) {
				$widget_data = $this->find_widget_recursive( $document->get_elements_data(), $widget_id );
				if ( $widget_data && isset( $widget_data['settings']['template_id'] ) ) {
					$template_id = $widget_data['settings']['template_id'];
				}
			}
		}

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'Template ID not found.' ] );
		}

		$is_mobile      = wp_is_mobile();
		$posts_per_page = $is_mobile ? 3 : -1;

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged'          => $page,
			'tax_query'      => [ 'relation' => 'AND' ],
		];

		// Apply category filter based on type
		if ( 'addons' === $filter_type ) {
			$args['tax_query'][] = [ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'add-ons', 'operator' => 'IN' ];
		} else {
			$args['tax_query'][] = [ 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'add-ons', 'operator' => 'NOT IN' ];
		}

		// Apply tag filter
		if ( ! empty( $selected_tags ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => $selected_tags,
				'operator' => 'AND',
			];
		}

		// --- FIX: Use the global namespace for WordPress's WP_Query class ---
		$query = new \WP_Query( $args );

		// Use Elementor's filter hook to prevent printing inline CSS
/* 		if ( class_exists( '\Elementor\Plugin' ) ) {
			add_filter( 'elementor/frontend/should_print_css', '__return_false' );
		} */

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( class_exists( '\Elementor\Plugin' ) ) {
					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, true );
				}
			}
		} else {
			$options            = get_option( 'dgcpf_options', [] );
			$no_products_text   = isset( $options['no_products_text'] ) ? $options['no_products_text'] : 'There are no products with that combination of tags.';
			echo '<p class="no-products-found">' . esc_html( $no_products_text ) . '</p>';
		}
		$html = ob_get_clean();
		wp_reset_postdata();

		// Remove the filter to not affect other parts of the page
/* 		if ( class_exists( '\Elementor\Plugin' ) ) {
			remove_filter( 'elementor/frontend/should_print_css', '__return_false' );
		} */

		// Find available tags for the remaining products
		$available_tags       = [];
		$available_tags_args  = $args;
		$available_tags_args['fields'] = 'ids';
		$matching_product_ids = get_posts( $available_tags_args );

		if ( ! empty( $matching_product_ids ) ) {
			$terms = wp_get_object_terms( $matching_product_ids, 'product_tag', [ 'fields' => 'all' ] );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$available_tags[ $term->slug ] = $term->name;
				}
			}
		}

		wp_send_json_success( [
			'html'           => $html,
			'max_pages'      => $query->max_num_pages,
			'available_tags' => $available_tags,
		] );
	}

	/**
	 * Helper function to find a widget in Elementor's data structure.
	 */
	private function find_widget_recursive( $elements, $widget_id ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['id'] ) && $widget_id === $element['id'] ) {
				return $element;
			}
			if ( ! empty( $element['elements'] ) ) {
				$found = $this->find_widget_recursive( $element['elements'], $widget_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return false;
	}
}
