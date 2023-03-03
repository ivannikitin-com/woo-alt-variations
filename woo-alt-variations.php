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
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_variaton_tab' ), 10, 1 );
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_variaton_tab_panel' ));
        add_action( 'woocommerce_process_product_meta', array( $this, 'alt_variaton_fields_save' ), 10 );
        add_action( 'woocommerce_single_product_summary', array( $this, 'output_alt_variations_links' ), 19 );
        add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts_and_styles' ), 10  );
        add_action( 'woocommerce_product_query', array($this, 'add_product_tax_query'), 99000 );
        add_action( 'wp_ajax_add_alt_variation_attribute', array($this, 'add_alt_variation_attribute' ));
        add_action( 'wp_ajax_add_alt_variation_product', array($this, 'add_alt_variation_product' ));
        add_action( 'woocommerce_shop_loop_item_title', array($this, 'output_variations_quantity' ), 5);
        add_action( 'plugins_loaded', array( $this, 'add_image_thumb'), 0) ;
        add_filter( 'comments_template', array( $this, 'aggr_comments'), 100, 1 );
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
            'var_product_visibility', // таксономия
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
/*        wp_enqueue_script( 'images-loaded', 'https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js', array( 'jquery'), null, true );*/
        wp_enqueue_script( 'alt-variations', plugins_url( '/js/woo-alt-variations.js' , __FILE__ ), array( 'jquery'), null, true );

    }

    public function add_product_tax_query($query ) {
        global $WOOF;

/*        echo '<script id="alt-var">';
        print_r($query);
        echo '</script>';*/
                
        if (isset($WOOF)) {
            $filter_request = $WOOF->get_request_data();
        } else {
            $filter_request = array();
        }

        //file_put_contents('add_product_tax_query.log',implode(",", $_GET).PHP_EOL,FILE_APPEND);

        // Only on Product Category archives pages
        if( is_admin() ||  ! is_product_category() || isset( $_GET['swoof'] ) || isset( $_GET['min_price'] ) || isset( $query->query_vars['prdctfltr_active'])) {
        //|| count($filter_request)           
            return; 
        }


        $tax_query = $query->get( 'tax_query' );
/*        if ( ! is_array( $tax_query ) ) {
            $tax_query = array(
                'relation' => 'AND',
            );
        } else {
            $tax_query['relation'] = 'AND';     
        }*/

        // The taxonomy for Product Categories
        $taxonomy = 'var_product_visibility';
/*        $taxonomy = 'product_tag';*/
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
        $alt_var_visibility = has_term( 'invisible', 'var_product_visibility', $product->get_id() );
        $vars_info = get_post_meta($product->get_id(),'vars_info',true);
        $vars_info_arr = json_decode($vars_info, true);
/*        echo "<pre>";
        print_r($vars_info_arr);
        echo "</pre>";*/
        ?>
        <div id="alt_variations_product_data" class="panel wc-metaboxes-wrapper hidden woocommerce_options_panel">
                
                <div class="toolbar toolbar-top">
                    <?php woocommerce_wp_checkbox(array( 
                        'id'            => 'var_product_visibility', 
                        'label'         => __('Считать вариацией', 'woocommerce' ), 
                        'description'   => __( 'Если отмечен, то данный товар на страницах каталога не показывается до тех пор, пока не применен фильтр.', 'woo-alt-variations' ),
                        'value'         => ($alt_var_visibility) ? 'yes' : 'no',
/*                        'cbvalue'         => ($alt_var_visibility) ? 'yes' : '',*/
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
        if (isset($_POST['var_product_visibility']) && $_POST['var_product_visibility']) {
                wp_set_object_terms($post_id, 'invisible', 'var_product_visibility');
        } else {
            wp_remove_object_terms( $post_id, 'invisible', 'var_product_visibility' );
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
     * Получение id всех товаров-вариаций данного товара
     */
/*    public function get_alt_variation_ids() {
        global $product;
        $vars_info = get_post_meta($product->get_id(),'vars_info', true);
        $variation_ids = array();
        if (!$vars_info) {
            return false;
        }
        $attr_groups = json_decode($vars_info, true);
        foreach ($attr_group['products'] as $group_product) :
            $variation_ids[] = $group_product['product_id'];
        endforeach;
        return $variation_ids;
    }*/

    /**
     * Вывод вариаций на страницу товарной категории товара
     */
    public function output_alt_variations_thumbs() {
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
        
        //foreach ( $attr_groups as $key=>$attr_group ) {
            $key = array_key_first($attr_groups);
            $attr_group = $attr_groups[$key];

            if (isset($attr_group['attr_name']) && $attr_group['attr_name']) {
                //Ищем значение данного атрибута у текущего товара

                $cur_var_attr_name = '';
                $var_quantity = 0;
                foreach ($attr_group['products'] as $group_product) {
                    if ($group_product['product_id'] == $product->get_id()) {
                        $cur_var_attr_name = $group_product['var_attr_value']; 
                    } else {
                        $var_quantity ++;
                    }
                } ?>
                
                <div class="attr_wrap">
                    <div class="attr_images_wrap">
                    <?php foreach ($attr_group['products'] as $group_product) : 
                        if ($group_product['image_id']) {
                            $thumbnail_src = wp_get_attachment_image(
                                $group_product['image_id'],
                                'attr_var_thumb'
                            );
                        } else {
                            $thumbnail_src = get_the_post_thumbnail($group_product['product_id'], $thumbnail_size);
                        }
                        if ($group_product['product_id'] == $product->get_id()) {
                            $active = " active";
                        } else {
                            $active = "";
                        }                        
                        if ( $thumbnail_src ) { ?>               
                            <div class="woocommerce-product-gallery__image hidden <?php echo $active; ?>">
                                    <div class="var_lnk_inner_wrap">
                                        <?php echo $thumbnail_src; ?>
                                    </div>
                            </div><!-- /.woocommerce-product-gallery__image -->
                        <?php } else { ?>
                            <div class="woocommerce-product-gallery__image--placeholder hidden <?php echo $active; ?>">
                                    <div class="var_lnk_inner_wrap">
                                        <img src="<?php echo esc_url( wc_placeholder_img_src( 'attr_var_thumb' ) ); ?>" alt="" class="wp-post-image" />
                                    </div>
                            </div><!-- /.woocommerce-product-gallery__image--placeholder --> 
                        <?php } ?>
                    <?php endforeach; ?>
                    <span class="var_quantity hidden ">
										<svg class="var_quantity-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99h144v-144C192 62.32 206.33 48 224 48s32 14.32 32 32.01v144h144c17.7-.01 32 14.29 32 31.99z"/></svg>
										<?php //echo $var_quantity; ?>
										</span>
                    </div>

                </div>

            <?php } ?>
        <?php //} ?>
        </div><!-- /.alt_variations_groups_wrap -->
        <?php //echo $html; ?>        
    <?php 
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
                $var_quantity = 0;
                foreach ($attr_group['products'] as $group_product) {
                    if ($group_product['product_id'] == $product->get_id()) {
                        $cur_var_attr_name = $group_product['var_attr_value']; 
                    } else {
                        $var_quantity ++;
                    }
                } ?>
                <button class="var_header" data-toggle="modal" data-target="#myModal_<?php echo $key; ?>"> 
                <span class="attr_wrap">
                    <span class="attr_title"><?php echo __('Выберите ','woo-alt-variations').$attr_group['attr_name']; ?></span>
                    <span class="attr_subtitle"><?php echo $cur_var_attr_name; ?></span>
                    <div class="attr_images_wrap">
                    <?php foreach ($attr_group['products'] as $group_product) : 
                        if ($group_product['image_id']) {
                            $thumbnail_src = wp_get_attachment_image(
                                $group_product['image_id'],
                                'attr_var_thumb'
                            );
                        } else {
                            //$thumbnail_src = '<img src="'.get_the_post_thumbnail_url($group_product['product_id'], $thumbnail_size).'" alt="" class="wp-post-image" />';
                            $thumbnail_src = get_the_post_thumbnail($group_product['product_id'], $thumbnail_size);
                        }
                        if ($group_product['product_id'] == $product->get_id()) {
                            $active = " active";
                        } else {
                            $active = "";
                        }                        
                        if ( $thumbnail_src ) { ?>               
                            <div class="woocommerce-product-gallery__image hidden <?php echo $active; ?>">
                                    <div class="var_lnk_inner_wrap">
                                        <?php echo $thumbnail_src; ?>
                                    </div>
                            </div><!-- /.woocommerce-product-gallery__image -->
                        <?php } else { ?>
                            <div class="woocommerce-product-gallery__image--placeholder hidden <?php echo $active; ?>">
                                    <div class="var_lnk_inner_wrap">
                                        <img src="<?php echo esc_url( wc_placeholder_img_src( 'attr_var_thumb' ) ); ?>" alt="" class="wp-post-image" />
                                    </div>
                            </div><!-- /.woocommerce-product-gallery__image--placeholder --> 
                        <?php } ?>
                    <?php endforeach; ?>
                    <span class="var_quantity hidden ">
											<svg class="var_quantity-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99h144v-144C192 62.32 206.33 48 224 48s32 14.32 32 32.01v144h144c17.7-.01 32 14.29 32 31.99z"/></svg>
										<?php //echo $var_quantity; ?>
										</span>
                    </div>

                </span>
                <i class="fa fa-chevron-right" aria-hidden="true" style="margin-right: 20px;font-size: 18px;"></i>
                </button>
            <?php } ?>
            <?php ob_start();?>
            <!--<div class="fancybox-hidden" style="width:auto; max-width: 30rem;">-->
            <div class="modal fade variations" id="myModal_<?php echo $key; ?>" tabindex="-1" role="dialog" aria-labelledby="myModal_<?php echo $key; ?>Label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
							
                <div id="alt_variations_wrap_<?php echo $key; ?>" class="alt_variations_wrap">
                <?php 
                if (isset($attr_group['attr_name']) && $attr_group['attr_name']) { ?>
                    <div class="alt_variations_title">Выберите <?php echo $attr_group['attr_name']; ?></div>
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
                        //$thumbnail_src = wp_get_attachment_image_src( $group_product['image_id'], $thumbnail_size );
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
                <!--<button type="button" class="additional_close bottom_close" data-dismiss="modal" aria-hidden="true"><span>Закрыть окно</span> <img src="<?php echo plugin_dir_url( __FILE__ ); ?>/img/close.svg"></button>-->
                </div><!-- /#alt_variations_wrap_<?php echo $key; ?> -->
				
                        </div><!-- /.modal-body -->
								<div class="modal__btn-close">
										<!--<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>-->
										<button type="button" class="additional_close" data-dismiss="modal" aria-hidden="true">
										<span class="modal__btn-label">Закрыть окно</span>
									</button>
								</div>
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

    /**
     * Вывод количества вариаций товара на страницу товарной категории
     */
    public function output_variations_quantity() {
        global $product;
        $vars_info = get_post_meta($product->get_id(),'vars_info', true);
        if (!$vars_info) {
            return;
        }
        $var_quantity = substr_count($vars_info, 'product_id');
        $minus =  substr_count($vars_info, '"'.$product->get_id().'"' );
        $var_quantity = $var_quantity - $minus;
        if ($var_quantity) {
            //echo '<div class="more_vars">Больше вариантов +'.$var_quantity.'</div>';
            $this->output_alt_variations_thumbs();
        }  
    }

    /**
     * Добавление размера миниатюры для вывода миниатюр вариаций в карточку товара
     */
    public function add_image_thumb(){
        add_image_size( 'attr_var_thumb', 50, 50, true );
    }

    public function aggr_comments( $template ) {
        global $woocommerce;
        if ( get_post_type() == 'product' && file_exists( untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/single-product-reviews.php' ) ) {
            return untrailingslashit( plugin_dir_path( __FILE__ ) . '/woocommerce/single-product-reviews.php');
        }
        return $woocommerce->comments_template_loader($template);
    }

}

// Инициализация плагина
$woo_alt_variations = new Woo_Alt_Variations();
include(plugin_dir_path( __FILE__ ).'woo-alt-shortcodes.php');
