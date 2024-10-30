<?php

namespace MDT\Reviews;

/**
 * Output Review data in the Rest API
 */
class API
{
    /**
     * Setup class
     *
     * @return void
     */
    public static function init()
    {
        add_action('rest_api_init', array( __CLASS__, 'register_field' ));
        add_filter('wp_rest_yoast_meta/filter_yoast_json_ld', array( __CLASS__, 'filter_yoast_json_ld' ));
    }


    /**
     * Register new field for MDT Review
     */
    public static function register_field()
    {
        register_rest_field(
            'post',
            'mdt_review',
            array(
                'methods'      => 'GET',
                'get_callback' => array( __CLASS__, 'get_review' ),
            )
        );
    }


    /**
     * Output review data
     *
     * @param [type] $post
     * @param [type] $field_name
     * @param [type] $request
     * @return void
     */
    public static function get_review($post, $field_name, $request)
    {
        return \MDT\Reviews\Admin::get_review_data($post);
    }


    /**
     * If Yoast is enabled, filter the schema data into theirs
     * For non-Yoast users, we presume you will set your Schema data directly via the fields provided above
     *
     * @param array $meta The Yoast JSON+LD
     *
     * @return array The modified array
     */
    public static function filter_yoast_json_ld($json_ld)
    {
        global $post;
        $review_json = \MDT\Reviews\Schema::get_structured_data($post);
        if (!empty($review_json) && !empty($review_json['name'])) {
            $json_ld[0]['@graph'][] = $review_json;
        }
        return $json_ld;
    }
}

API::init();
