<?php
/**
 * Plugin Name:       Woocommerce Alternative Vatiations
 * Description:       Another mechanizm to link products as variations. No parent product.
 * Version:           1.0.0
 * Author:            ИП Никитин и партнеры
 * License:           GPL-2.0+
 * Text Domain:       woo-alt-variations
 */

class Woo_Alt_Variations {

    /**
     * Инициализация плагина.
     */
    public function __construct() {
        $plugin = plugin_basename( __FILE__ );
        add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ), 9 );
        add_action( 'plugins_loaded', array( $this, 'load' ) );
        $this->load_content_types();
        $this->add_terms();
        if( is_admin() ) {
            //add_action( 'current_screen', array( $this, 'current_screen') );
        }
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_variaton_tab'), 10, 1 );
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_variaton_tab_panel' ));
        add_action( 'woocommerce_process_product_meta', array( $this, 'alt_variaton_fields_save'), 10 );
        add_action( 'woocommerce_share', array( $this, 'output_alt_variations_links'), 99 );
        add_action( 'wp_enqueue_scripts', array($this, 'plugin_scripts_and_styles'), 10  );
        add_action( 'woocommerce_product_query', array($this, 'add_product_tax_query', 1000000, 2 ) );
        add_action( 'wp_ajax_add_alt_variation_attribute', array($this, 'add_alt_variation_attribute' ));
        add_action( 'wp_ajax_add_alt_variation_product', array($this, 'add_alt_variation_product' ));   
    }

    /**
     * Хук активации.
     *
     * Создаёт все нужные страницы для входа пользователя в систему.
     */
    public static function plugin_activated() {

    }

    public function check_woocommerce() {
        if ( $this->is_woocommerce_activated() === false ) {
            $error = sprintf( __( 'Woocommerce alternative variations requires %sWooCommerce%s to be installed & activated!' , 'woo-alt-variations' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>' );
            $message = '<div class="error"><p>' . $error . '</p></div>';
            echo $message;
            return;
        }
    }

    public function is_woocommerce_activated() {
        $blog_plugins = get_option( 'active_plugins', array() );
        $site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

        if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function load() {
        load_plugin_textdomain( 'woo-alt-variations', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    // Load Content Types
    protected function load_content_types() {
        require_once( 'woo-alt-vars-content-types.php' );
    }

    // Load Content Types
    protected function add_terms() {
        $insert_res = wp_insert_term(
            'invisible',  // новый термин
            'product', // таксономия
            array(
                'slug'        => 'invisible',
            )
        );
    }    

    public function current_screen() {
        $cs = get_current_screen();
        //if ( $cs->post_type == 'post' ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        //}
    }

    public function admin_scripts() {
        wp_enqueue_style( 'alt_variations_admin_styles', plugins_url('/css/admin-styles.css', __FILE__) );
        wp_enqueue_script( 'admin-alt-variations', plugins_url( '/js/admin-woo-alt-variations.js' , __FILE__ ), array( 'jquery'), null, true );

    }

    public function plugin_scripts_and_styles() {
        wp_enqueue_style( 'alt_variations_styles', plugins_url('/css/styles.css', __FILE__) );
    }

    public function add_product_tax_query($query ) {
    /*  echo '<pre>';
        echo '<pre>';
        print_r($query);
        echo '</pre>';*/    
        // Only on Product Category archives pages
        if( is_admin() || ! is_product_category() || isset( $_GET['swoof'] ) || isset( $query->query_vars['prdctfltr_active'])) {
            return; 
        }

        $tax_query = $query->get( 'tax_query' );
        if ( ! is_array( $tax_query ) ) {
            $tax_query = array(
                'relation' => 'AND',
            );
        } else {
            $tax_query['relation'] = 'AND';     
        }

        // The taxonomy for Product Categories
        $taxonomy = 'var_product_visibility';

            $tax_query[] = array(
                'taxonomy'       => $taxonomy,
                'field'   => 'slug',
                'terms'     => array( "invisible"),
                'operator'   => 'NOT IN'
            );
        $query->set('tax_query', $tax_query);
     
    }

    /**
     * Создание произвольной вкладки
     *
     * @param  array $tabs Массив вкладок.
     * @return array
     */
    public function add_variaton_tab( array $tabs ): array {

        $tabs['alt_variations'] = [
            'label'    => __('Вариации', 'woo-alt-variations'), // название вкладки.
            'target'   => 'alt_variations_product_data', // идентификатор вкладки.
            'class'    => [ 'show_if_simple hide_if_grouped hide_if_variable' ], // классы управления видимостью вкладки в зависимости от типа товара.
            'priority' => 25 // приоритет вывода.
        ];

        return $tabs;
    }

    /**
     * Вывод данных на вкладку "Вариации"
     */
    public function add_variaton_tab_panel() {
        $product = wc_get_product();
        $alt_var_visibility = has_term( 'invisible', 'var_product_visibility', $product );
        $vars_info = get_post_meta($product->get_id(),'vars_info',true);
        $vars_info_arr = json_decode($vars_info, true);
        ?>
        <div id="alt_variations_product_data" class="panel wc-metaboxes-wrapper hidden woocommerce_options_panel">
                
                <div class="toolbar toolbar-top">
                    <?php woocommerce_wp_checkbox(array( 
                        'id'            => 'var_product_visibility', 
                        'label'         => __('Считать вариацией', 'woocommerce' ), 
                        'description'   => __( 'Если отмечен, то данный товар на страницах каталога не показывается до тех пор, пока не применен фильтр.', 'woo-alt-variations' ),
                        'value'         => ($alt_var_visibility) ? 'yes' : 'no',
                        'cbvalue'         => ($alt_var_visibility) ? 'yes' : '',
                    )); ?>
                    <a class="button add_variation_attribute"><?php _e('Добавить атрибут вариации ','woo-alt-variations'); ?></a>
                </div>
                <div class="alt_variations">
                    <?php if ($vars_info) {
                        foreach ($vars_info_arr as $group_key => $group_value) {
                            if ($group_value['products']) {
                                /*foreach ($group_value['products'] as $product_key => $product_value) {*/
                                    load_template(dirname( __FILE__ ) .'/html-alt-variation-attr-admin.php', false, array(
                                        'group_id' => $group_key,
                                        'attr_name' => $group_value['attr_name'],
                                        'var_products' => $group_value['products'],
                                        'var_product_id' => $product_key)
                                    );

                                /*}*/
                            } else {
                                load_template(dirname( __FILE__ ) .'/html-alt-variation-attr-admin.php', false, array(
                                    'group_id' => $group_key,
                                    'attr_name' => $group_value['attr_name'],
                                    'var_products' => array(),
                                    'var_product_id' => 0)
                                );
                            }
                        }
                    }
                    ?>
                </div>
        </div>
        <!-- </div> -->
    <?php
    }

    /**
     * Сохранение данных из вкладки "Вариации"
     */
    public function alt_variaton_fields_save($post_id) {
        //Сохраняем признак видимости данного товара в каталоге
        if (isset($_POST['var_product_visibility'])) {
            if ($_POST['var_product_visibility']) {
                wp_set_object_terms($post_id, 'invisible', 'var_product_visibility');
            } else {
                wp_remove_object_terms( $post_id, 'invisible', 'var_product_visibility' );
            }
        } 
        //Собираем массив данных по вариациям    
        if (isset($_POST['var_group'][0])){
            $vars_info = array();
            foreach($_POST['var_group'] as $group_key=>$group_value) {
                $vars_info[$group_key] = array(
                    'attr_name' => $group_value, 
                );
                foreach($_POST['product_id'][$group_key]  as $prod_key=>$prod_value) {
                    $vars_info[$group_key]['products'][] = array(
                            'product_id' => $_POST['product_id'][$group_key][$prod_key],
                            'image_id' => $_POST['image_id'][$group_key][$prod_key],
                            'var_attr_value' => $_POST['var_attr_value'][$group_key][$prod_key]
                    );
                }
            }
            update_post_meta( $post_id, 'vars_info', json_encode($vars_info, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Вывод вариаций на страницу карточки товара
     */
    public function output_alt_variations_links() {
        global $product;
        //$thumbnail_size        = wc_get_image_size( 'gallery_thumbnail' );
        //$thumbnail_size = 'gallery_thumbnail';
        $gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
        $thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
        $image_size        = $thumbnail_size;        

        $vars_info = get_post_meta($product->get_id(),'vars_info', true);
        if (!$vars_info) {
            return;
        }
        
        $attr_groups = json_decode($vars_info, true);
        $cur_var_attr_name = ''; ?>
        <div class="alt_variations_groups_wrap">

        <?php

        $html = "";
        
        foreach ( $attr_groups as $key=>$attr_group ) {
            if (isset($attr_group['attr_name']) && $attr_group['attr_name']) {
                //Ищем значение данного атрибута у текущего товара

                $cur_var_attr_name = '';
                foreach ($attr_group['products'] as $group_product) {
                    if ($group_product['product_id'] == $product->get_id()) {
                        $cur_var_attr_name = $group_product['var_attr_value']; 
                    }
                } ?>
                <!--onclick="jQuery.fancybox({
                    'href':'#alt_variations_wrap_<?php echo $key; ?>',
                    'minWidth':270,
                    'maxWidth':500,
                    'height': '100%',
                    'autoDimensions': false,
                    'centerOnScroll': false,
                    'fitToView': false,
                    'top': 0,
                    'mainClass': 'alt_variations'
                })" -->
                <button class="var_header" data-toggle="modal" data-target="#myModal_<?php echo $key; ?>"> 
                <span class="attr_wrap">
                <span class="attr_title"><?php echo __('Выберите ','woo-alt-variations').$attr_group['attr_name']; ?></span>
                <span class="attr_subtitle"><?php echo $cur_var_attr_name; ?></span>
                </span>
                <svg focusable="false" viewBox="0 0 24 24" class="range-revamp-svg-icon range-revamp-chunky-header__icon" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="m15.5996 12.0007-5.785 5.7857-1.4143-1.4141 4.3711-4.3716L8.4003 7.629l1.4143-1.4142 5.785 5.7859z"></path></svg>
                </button>
            <?php } ?>
            <?php ob_start();?>
            <!--<div class="fancybox-hidden" style="width:auto; max-width: 30rem;">-->
            <div class="modal fade variations" id="myModal_<?php echo $key; ?>" tabindex="-1" role="dialog" aria-labelledby="myModal_<?php echo $key; ?>Label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <div id ="alt_variations_wrap_<?php echo $key; ?>" class="alt_variations_wrap">
                <?php 
                if (isset($attr_group['attr_name']) && $attr_group['attr_name']) { ?>
                    <div class="alt_variations_title"><?php echo $attr_group['attr_name']; ?></div>
                <?php 
                }
                foreach ($attr_group['products'] as $group_product) {
                    $var_product = wc_get_product( $group_product['product_id'] );
                    if (!$var_product) {
                        continue;
                    }                
                    if ($group_product['product_id'] == $product->get_id()) {
                        $active = " active";
                    } else {
                        $active = "";
                    }
                    if ($group_product['image_id']) {
                        $thumbnail_src = wp_get_attachment_image_src( $group_product['image_id'], $thumbnail_size );
                        $thumbnail_src = wp_get_attachment_image(
                            $group_product['image_id'],
                            'cust_shop_thumbnail'
                        );
                    } else {
                        $thumbnail_src = get_the_post_thumbnail_url($group_product['product_id'], $thumbnail_size);
                    }
                    $var_attr_value = isset($group_product['var_attr_value'])?$group_product['var_attr_value']:"";

                    if ( $thumbnail_src ) { ?>               
                    <div class="woocommerce-product-gallery__image<?php echo $active; ?>">
                        <a href="<?php echo get_permalink($group_product['product_id']); ?>">
                            <div class="var_lnk_inner_wrap">
                                <?php echo $thumbnail_src; ?>
                                <div class="var_title"><?php echo $var_attr_value; ?></div>
                                <div class="var_title"><?php echo $var_product->get_price_html(); ?></div>
                            </div>
                        </a>
                    </div><!-- /.woocommerce-product-gallery__image -->
                    <?php } else { ?>
                    <div class="woocommerce-product-gallery__image--placeholder<?php echo $active; ?>">
                       <a href="<?php echo get_permalink($group_product['product_id']); ?>">
                            <div class="var_lnk_inner_wrap">
                                <img src="<?php echo esc_url( wc_placeholder_img_src( $thumbnail_size ) ); ?>" alt="" class="wp-post-image" />
                                <div class="var_title"><?php echo $var_attr_value; ?></div>
                                <div class="var_title"><?php echo $var_product->get_price_html(); ?>-</div>
                            </div>
                        </a>
                    </div><!-- /.woocommerce-product-gallery__image--placeholder --> 
                    <?php }             
                } ?>
                </div><!-- /#alt_variations_wrap_<?php echo $key; ?> -->
                        </div><!-- /.modal-header -->
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div>
            <?php $html .= ob_get_clean(); ?>
        <?php } ?>
        </div><!-- /.alt_variations_groups_wrap -->
        <?php echo $html; ?>        
    <?php 
    }

    /**
     * Добавление группы вариаций в админке через ajax
     */
    public function add_alt_variation_attribute() {
        if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['post_id'], $_POST['group_id'], $_POST['var_product_id'] ) ) {
            wp_die( -1 );
        }       

        global $post; // Set $post global so its available, like within the admin screens.
        $product_ids = explode(",",get_post_meta( $_POST['post_id'],'vars_ids',true));         

        $product_id       = intval( $_POST['post_id'] );
        $post             = get_post( $product_id ); // phpcs:ignore
        $group_id         = intval( $_POST['group_id'] );
        $var_product_id   = intval( $_POST['var_product_id'] );
        /*$product_object   = wc_get_product_object( 'variable', $product_id ); // Forces type to variable in case product is unsaved.
        $variation_object = wc_get_product_object( 'variation' );
        $variation_object->set_parent_id( $product_id );
        $variation_object->set_attributes( array_fill_keys( array_map( 'sanitize_title', array_keys( $product_object->get_variation_attributes() ) ), '' ) );
        $variation_id   = $variation_object->save();
        $variation      = get_post( $variation_id );
        $variation_data = array_merge( get_post_custom( $variation_id ), wc_get_product_variation_attributes( $variation_id ) ); // kept for BW compatibility.*/
        load_template(dirname( __FILE__ ) .'/html-alt-variation-attr-admin.php', false, array('group_id' => $group_id, 'var_product_id' => $var_product_id));
        wp_die();
    }

    /**
     * Добавление одной вариации в группу вариаций в админке через ajax
     */
    public function add_alt_variation_product() {
        if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['post_id'], $_POST['var_product_id'], $_POST['group_id'] ) ) {
            wp_die( -1 );
        }       

        global $post; // Set $post global so its available, like within the admin screens.
        $product_ids = explode(",",get_post_meta( $_POST['post_id'],'vars_ids',true));         

        $product_id       = intval( $_POST['post_id'] );
        $post             = get_post( $product_id ); // phpcs:ignore
        $var_product_id   = intval( $_POST['var_product_id'] );
        $group_id = $_POST['group_id'];
        load_template(dirname( __FILE__ ) .'/html-alt-variation-product-admin.php', false, array('group_id' => $group_id, 'var_product_id' => $var_product_id)); 
        wp_die();
    }    

}

// Инициализация плагина
$woo_alt_variations = new Woo_Alt_Variations();