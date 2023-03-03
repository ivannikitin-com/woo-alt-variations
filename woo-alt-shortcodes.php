<?php
/**
 * Add alt_var shortcode
 *
 */
function woo_alt_var_shortcode( $atts = array(), $content = null ) {
	global $product, $woo_alt_variations, $aggr_comments, $comments;
	
/*		extract( shortcode_atts( array(
		'style'    => 'masonry',
		'number'   => '-1',
		'column'   => '3',
		'cat'      => '',
		'filter'   => 'true',    // display filter or not
		'all_text' => __( 'All', 'pencidesign' ),
	), $atts ) );*/

    $vars_info = get_post_meta($product->get_id(),'vars_info', true);
/*    if (!$vars_info) {
        return;
    }*/
    $variation_ids = array();
	$attr_groups = json_decode($vars_info, true);
   	foreach ( $attr_groups as $key=>$attr_group ) {
	    foreach ($attr_group['products'] as $group_product) {
	        $variation_ids[] = $group_product['product_id'];
	    }
    }
/*    if (!count($variation_ids)) {
    	return;
    }*/

    if (count($variation_ids) > 0 ) {
    	$post_in = $variation_ids;
   	} else {
   		$post_in = array($product->get_id());
   	}

	$aggr_comments = get_comments(array(
/*		'number'      	=> -1,*/
		'post__in'		=> $post_in,
		'status'      	=> 'approve',
		'post_status' 	=> 'publish',
		'parent'     	=> 0
	));

    //$comments = $aggr_comments;
}

add_shortcode( 'all_var_reviews' , 'woo_alt_var_shortcode' );