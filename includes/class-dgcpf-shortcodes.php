<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class DGCPF_Shortcodes
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_shortcode('product_tag_filter', [$this, 'render_product_tag_filter']);
        add_shortcode('add_ons_tag_filter', [$this, 'render_add_ons_tag_filter']);
    }

    /**
     * Renders the main product tag filter.
     */
    public function render_product_tag_filter()
    {
        $valid_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'add-ons',
                    'operator' => 'NOT IN',
                ],
            ],
        ]);

        $all_tags = get_terms([
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
            'object_ids' => !empty($valid_product_ids) ? $valid_product_ids : [0],
        ]);

        return $this->render_filter_html($all_tags, 'products');
    }

    /**
     * Renders the add-ons tag filter.
     */
    public function render_add_ons_tag_filter()
    {
        $valid_product_ids = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => 'add-ons',
                    'operator' => 'IN',
                ],
            ],
        ]);

        $all_tags = get_terms([
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
            'object_ids' => !empty($valid_product_ids) ? $valid_product_ids : [0],
        ]);

        return $this->render_filter_html($all_tags, 'addons');
    }

    /**
     * Renders the filter HTML by loading a template file.
     */
    private function render_filter_html($tags, $filter_type)
    {
        ob_start();
        // This makes it easy to edit the HTML later without touching the logic.
        include(DGCPF_PLUGIN_DIR . 'templates/filter-ui.php');
        return ob_get_clean();
    }
}