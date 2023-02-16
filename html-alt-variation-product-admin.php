<?php 
$group_id = $args['group_id'];
$var_product_id = $args['var_product_id'];
$var_product_data = $args['var_product_data'];
if (isset($var_product_data['product_id'])) {
    $var_product_value = $var_product_data['product_id'];
} else {
    $var_product_value = ''; 
}
if (isset($var_product_data['image_id'])) {
    $var_image_value = $var_product_data['image_id'];
} else {
    $var_image_value = '';
}
if (isset($var_product_data['var_attr_value'])) {
    $var_attr_value = $var_product_data['var_attr_value'];
} else {
    $var_attr_value = '';
}
?>
<div class="options_group ui-sortable-handle">
    <div class="variation_product_wrap">
        <!-- <div class="ui-sortable-handle" style="width: 17px; height:17px;"></div> -->
        <div class="form-field product_id product_id_<?php echo $var_product_id; ?>_field ">
            <label for="product_id_<?php echo $group_id.'_'.$var_product_id; ?>"><?php _e('Товар(id)','woo-alt-variations'); ?>
<!--             <input type="text" class="wc-product-search wc-enhanced-select" style="" name="product_id[<?php //echo $group_id.']['.$var_product_id; ?>]" id="product_id_<?php //echo $group_id.'_'.$var_product_id; ?>" value="<?php //echo $var_product_value; ?>" placeholder="" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo $var_product_value; ?>"> -->
            </label>
            <select class="wc-product-search" id="product_id_<?php echo $group_id.'_'.$var_product_id; ?>" name="product_id[<?php echo $group_id.']['.$var_product_id; ?>]" data-placeholder="Поиск по товарам…" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo $var_product_value; ?>">
                <option value="<?php echo $var_product_value; ?>" <?php echo selected( true, true, false ); ?> ><?php echo get_the_title($var_product_value); ?></option>
            </select>
        </div>

        <div class="form-field image_id image_id_<?php echo $var_product_id; ?>_field ">
            <a href="#" class="upload_image_button tips <?php echo ($var_image_value) ? 'remove' : ''; ?>" data-tip="<?php echo ($var_image_value) ? esc_attr__( 'Remove this image', 'woocommerce' ) : esc_attr__( 'Upload an image', 'woocommerce' ); ?>" rel="">
                <img src="<?php echo ($var_image_value) ? wp_get_attachment_image_url( $var_image_value, 'thumbnail') : esc_url( wc_placeholder_img_src() ); ?>" />
                <input type="hidden" name="image_id[<?php echo $group_id.']['.$var_product_id; ?>]" class="image_id" id="image_id_<?php echo $group_id.'_'.$var_product_id; ?>" value="<?php echo $var_image_value; ?>">
            </a>            
        </div>

        <div class="form-field var_attr_value var_attr_value_<?php echo $var_product_id; ?>_field ">
            <label for="var_attr_value_<?php echo $group_id.'_'.$var_product_id; ?>"><?php _e('Значение атрибута(текст)','woo-alt-variations'); ?><input type="text" class="" style="" name="var_attr_value[<?php echo $group_id.']['.$var_product_id; ?>]" id="var_attr_value_<?php echo $group_id.'_'.$var_product_id; ?>" value="<?php echo $var_attr_value; ?>" placeholder=""></label></div>
        <a href="#" class="button remove_variation_product" title="<?php _e('Удалить товар','woo-alt-variations'); ?>"><span class="dashicons dashicons-minus"></span></a>
    </div>
</div>