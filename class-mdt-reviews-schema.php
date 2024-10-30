<?php

namespace MDT\Reviews;

/**
 * Handle the creation and output of the Schema data
 */
class Schema
{

    /**
     * Bootstrap class
     *
     * @return void
     */
    public static function init()
    {
        add_action('wp_head', [ __CLASS__, 'add_structured_data' ]);
    }


    /**
     * Print the Schema data
     *
     * @return void
     */
    public static function add_structured_data()
    {

        // Only express on single posts.
        if (is_single()) {
            $structured_data = self::get_structured_data(get_the_ID());

            // JSON encode the output.
            if (! empty($structured_data) && ! empty($structured_data['name'])) {
                ?>
				<script type="application/ld+json">
				<?php echo wp_json_encode($structured_data); ?>
				</script>
				<?php
            }
        }
    }


    /**
     * Generate structured data markup
     *
     * @param [type] $post
     * @return void
     */
    public static function get_structured_data($post)
    {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $review_data = \MDT\Reviews\Admin::get_review_data($post);

        $structured_data = [
            '@context'	=> 'http://schema.org/',
            '@type'		=> $review_data['type'],
            'name'   	=> $review_data['title'],
            'sameAs' 	=> $review_data['url'],
            'image'  	=> get_the_post_thumbnail_url($post),
            'review' 	=> [
                'datePublished' => get_the_date('c', $post),
                'description'   => wp_strip_all_tags(get_the_excerpt($post)),
                'url'			=> get_permalink($post),
                'author'        => [
                    '@type'  => 'Person',
                    'name'   => get_the_author_meta('display_name', $post->post_author),
                ],
                'publisher'        => [
                    '@type'  => 'Organization',
                    'url'	=> home_url(),
                    'name'   => get_bloginfo('name'),
                ],
                'reviewBody'    => wp_strip_all_tags(get_the_excerpt($post)),
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => $review_data['rating'],
                    'bestRating'  => 5,
                    'worstRating' => 0,
                ],
            ],

        ];

        if ('Book' === $review_data['type']) {
            $structured_data['author']     = 'unknown';
            $structured_data['isbn'] = 0;
        }

        if ('Event' === $review_data['type']) {
            $structured_data['location']     = 'TBD';
            $structured_data['startDate'] = 'TBD';
        }

        return apply_filters('mdt_reviews_schema', $structured_data, $post);
    }
}

Schema::init();
