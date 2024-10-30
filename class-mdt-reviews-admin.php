<?php

namespace MDT\Reviews;

/**
 * Handle the CRUD of the Reviews data in wp-admin
 */
class Admin
{

    // Setting these as individual, discrete keys as opposed to an array because one may want to query directly per key,
    // e.g. `get all where mdt_review_rating > 3` or `get all where mdt_review_type = book`
    const META_KEY_RATING = 'mdt_review_rating';
    const META_KEY_TYPE = 'mdt_review_type';
    const META_KEY_TITLE = 'mdt_review_title';
    const META_KEY_URL = 'mdt_review_url';


    /**
     * Bootstrap the feature
     *
     * @return void
     */
    public static function init()
    {
        add_action('add_meta_boxes_post', [ __CLASS__, 'add_meta_box' ]);
        add_action('save_post', [ __CLASS__, 'save_field' ], 10, 2);
    }

    /**
     * Add the meta box.
     */
    public static function add_meta_box()
    {
        add_meta_box(
            'mdt-review',
            'Review',
            [ __CLASS__, 'render_meta_box' ],
            'post',
            'side',
            'low'
        );
    }


    /**
     * Meta box callback.
     *
     * @param \WP_Post $post The post object.
     */
    public static function render_meta_box($post)
    {
        $review_rating  = get_post_meta($post->ID, self::META_KEY_RATING, true);
        $review_type = get_post_meta($post->ID, self::META_KEY_TYPE, true);
        $review_title = get_post_meta($post->ID, self::META_KEY_TITLE, true);
        $review_url = get_post_meta($post->ID, self::META_KEY_URL, true);

        // Generate stars HTML.
        $stars = '';
        for ($i = 1; $i <= 5; $i ++) {
            $stars .= sprintf(
                '<span class="%s">☆</span>',
                (! empty($review_rating) && (5 - $review_rating) < $i) ? esc_attr('selected') : ''
            );
        }

        wp_nonce_field('mdt_review', 'mdt_review_nonce'); ?>

		<p class="howto"><small>If this post is a review, fill out the following fields to generate a Google rich snippet for
			search results.</small></p>

		<p>
		<label for="mdt_review_title"><strong>Title</strong></label><br/>
		<input type="text" name="<?php echo esc_attr(self::META_KEY_TITLE); ?>" id="<?php echo esc_attr(self::META_KEY_TITLE); ?>"
		       value="<?php echo esc_attr($review_title); ?>" style="width:100%"/><br/>
		<small class="howto">Title of the thing being reviewed.</small>
		</p>

		<p>
		<label><strong>Rating</strong></label><br/>
		<select name="<?php echo esc_attr(self::META_KEY_RATING); ?>" id="<?php echo esc_attr(self::META_KEY_RATING); ?>">
			<option value="0" <?php selected(0.5, $review_rating); ?>>0</option>
			<option value="0.5" <?php selected(0.5, $review_rating); ?>>0.5</option>
			<option value="1" <?php selected(1, $review_rating); ?>>1</option>
			<option value="1.5" <?php selected(1.5, $review_rating); ?>>1.5</option>
			<option value="2" <?php selected(2, $review_rating); ?>>2</option>
			<option value="2.5" <?php selected(2.5, $review_rating); ?>>2.5</option>
			<option value="3" <?php selected(3, $review_rating); ?>>3</option>
			<option value="3.5" <?php selected(3.5, $review_rating); ?>>3.5</option>
			<option value="4" <?php selected(4, $review_rating); ?>>4</option>
			<option value="4.5" <?php selected(4.5, $review_rating); ?>>4.5</option>
			<option value="5" <?php selected(5, $review_rating); ?>>5</option>
		</select>
		<small class="howto">Number of stars out of five.</small>
		</p>
		<p>
		<label><strong>Type</strong></label><br/>
		<select name="<?php echo esc_attr(self::META_KEY_TYPE); ?>" id="<?php echo esc_attr(self::META_KEY_TYPE); ?>">
		<?php
            $types = [
                'Book',
                'Episode',
                'Event',
                'Game',
                'Movie',
                'Product',
                'Recipe',
                'SoftwareApplication',
            ];
        foreach ($types as $type) {
            ?>
			<option value="<?php echo $type; ?>" <?php selected($type, $review_type); ?>><?php echo $type; ?></option>
				<?php
        } ?>
		</select>
		</p>
		<p>
		<label for="mdt_review_url"><strong>URL</strong></label>
		<input type="text" name="<?php echo esc_attr(self::META_KEY_URL); ?>" id="<?php echo esc_attr(self::META_KEY_URL); ?>"
		       value="<?php echo esc_attr($review_url); ?>" style="width:100%" />
		<small class="howto">Link to the item's reference URL, e.g.
			<a href="https://www.imdb.com/title/tt1825683/" target="_blank">https://www.imdb.com/title/tt1825683/</a>
		</small>
		</p>
		<?php
    }


    /**
     * Save the field data.
     *
     * @param int $post_id The post ID being saved.
     * @param \WP_Post $post The post object being saved.
     */
    public static function save_field($post_id, $post)
    {

        // Autosave, revision, permission and nonce checks.
        if (wp_is_post_autosave($post_id)
             || wp_is_post_revision($post_id)
             || 'post' !== $post->post_type
             || ! isset($_POST['mdt_review_nonce'])
             || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mdt_review_nonce'])), 'mdt_review')
        ) {
            return;
        }

        $keys = [
            self::META_KEY_RATING => 'sanitize_text_field',
            self::META_KEY_TYPE => 'sanitize_text_field',
            self::META_KEY_TITLE => 'sanitize_text_field',
            self::META_KEY_URL => 'esc_url_raw',
        ];

        foreach ($keys as $key=>$sanitizer) {
            $current_value = (! empty($_POST[ $key ])) ? call_user_func($sanitizer, $_POST[ $key ]) : '';
            if (! empty($current_value)) {
                update_post_meta($post_id, $key, $current_value);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $post
     * @return void
     */
    public static function get_review_data($post)
    {
        $review = [];

        if (is_array($post) && !empty($post['id'])) {
            $post = get_post($post['id']);
        }

        if (is_numeric($post)) {
            $post = get_post($post);
        }

        if (is_a($post, '\WP_Post')) {
            $review = [
            'title' => get_post_meta($post->ID, \MDT\Reviews\Admin::META_KEY_TITLE, true),
            'rating' => (float) get_post_meta($post->ID, \MDT\Reviews\Admin::META_KEY_RATING, true),
            'type' => get_post_meta($post->ID, \MDT\Reviews\Admin::META_KEY_TYPE, true),
            'url' => get_post_meta($post->ID, \MDT\Reviews\Admin::META_KEY_URL, true),
        ];
        }

        $review = apply_filters('mdt_reviews_api_field', $review);

        return $review;
    }
}

Admin::init();
