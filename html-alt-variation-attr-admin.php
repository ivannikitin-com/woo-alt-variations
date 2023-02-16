<?php
/**
 * Outputs a variation for editing.
 *
 * @package WooCommerce\Admin
 * @var int $variation_id
 * @var WP_POST $variation
 * @var WC_Product_Variation $variation_object
 * @var array $variation_data array of variation data @deprecated 4.4.0.
 */

defined( 'ABSPATH' ) || exit;

?>
<?php 
$group_id = $args['group_id'];
$attr_name = $args['attr_name'];
$var_products = isset($args['var_products'])?$args['var_products']:array();
?>
<div class="alt_variation wc-metabox open" id="<?php echo $group_id;?>">
    <h3>
        <div class="form-field var_group_<?php echo $group_id;?>_field ">
            <label for="var_group_<?php echo $group_id;?>"><?php _e('Атрибут','woo-alt-variations'); ?></label>
            <input type="text" class="short" style="" name="var_group[<?php echo $group_id; ?>]" id="var_group_<?php echo $group_id; ?>" value="<?php echo $attr_name; ?>" placeholder="Укажите название, которое будет показываться в карточке товара">
            <!-- <a href="#" class="remove_variation_attribute delete" rel="<?php //echo esc_attr( $group_id ); ?>"><?php //esc_html_e( 'Remove', 'woocommerce' ); ?></a> -->
            <a href="#" class="button remove_variation_attribute" title="<?php _e('Удалить атрибут','woo-alt-variations'); ?>"><span class="dashicons dashicons-minus"></span></a>
            <!-- <div class="handlediv" aria-label="<?php //esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div> -->
        </div>            

    </h3>
    <div class="woocommerce_variable_attributes wc-metabox-content">
                <a class="button add_variation_product"><?php _e('Добавить товар','woo-alt-variations'); ?></a>
                    <div class="sortable_wrap">
                <?php 
                if ($var_products) {
                    foreach($var_products as $var_product_key=>$var_product_data) {
                        load_template(dirname( __FILE__ ) .'/html-alt-variation-product-admin.php', false, array(
                            'group_id' => $group_id,
                            'var_product_id' => $var_product_key,
                            'var_product_data' => $var_product_data ));
                    }
                } else {
                    //load_template(dirname( __FILE__ ) .'/html-alt-variation-product-admin.php', false, array('group_id' => $group_id, 'var_product_data' => array(), 'var_product_id' => 0));
                }
                ?>
                    </div>
    </div>
</div>
<?php
