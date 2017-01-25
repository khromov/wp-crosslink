<?php
/*
Plugin Name: CrossLink
Plugin URI: http://wordpress.org/extend/plugins/crosslink
Description: Automatically cross-link taxonomy terms to improve internal linking on your site
Author: khromov
Version: 0.9
Author URI: https://khromov.se
*/

/**
 * Filter the content
 */
add_filter('the_content', function($content) {

	//Is this a valid loop and should we run for this post type? FIXME: Allow to use only on main loop!!!
	if(get_the_ID() && in_array(get_post_type(), apply_filters('crosslink_post_types', array('post', 'page')))) {

		//Get all terms
		$terms = get_terms(apply_filters('crosslink_taxonomy', 'post_tag'), array( //category
			'hide_empty' => apply_filters('crosslink_taxonomy_hide_empty', false),
		));

		//Assemble term array. TODO: Cache call
		$term_array = array();
		foreach($terms as $term) {
			//Run through wptexturize to get the correct encoding we're going to see in the_content for single quotes and the like
			$term_name = wptexturize($term->name);
			$term_array[$term_name] = $term->term_id;
		}

		//Search for terms
		foreach($term_array as $term_name => $term_id) {
			$term_url = get_term_link($term_id);

			//Catch cases of s at end, TODO: Nicer stemmer approach?
			$content = preg_replace('/\b'. $term_name . '(s?)\b/ui', "[crosslink_internal href='{$term_url}' name='{$term_name}$1' /]", $content);
		}

		//Remove double links before outputting
		$content = preg_replace('/<a.*?(\[crosslink_internal.*?\/\]).*?<\/a>/ui', '$1', $content);

		return do_shortcode($content);
	}

	return $content;
}, 999);

/**
 * Add internal crosslink shortcode
 */
add_action('init', function() {
	add_shortcode('crosslink_internal', function($atts, $content) {
		extract( shortcode_atts( array(
			'href' => '',
			'name' => ''
		), $atts ));

		return "<a class='crosslink_link' href='{$href}'>{$name}</a>";
	});
});