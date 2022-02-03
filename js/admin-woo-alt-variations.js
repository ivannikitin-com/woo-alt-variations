jQuery( function( $ ) {
    'use strict';
    var wc_meta_boxes_product_alt_variations_ajax = {
        init: function() {
            $( '#alt_variations_product_data' )
                .on( 'click', '.remove_variation_attribute', this.remove_variation_attribute )
                .on( 'click', '.add_variation_attribute', this.add_variation_attribute );
            $( '#alt_variations_product_data' )
                .on( 'click', '.remove_variation_product', this.remove_variation_product )
                .on( 'click', '.add_variation_product', this.add_variation_product );
        },
        add_variation_attribute: function( event ) {
            /*wc_meta_boxes_product_variations_ajax.block();*/

            var data = {
                action: 'add_alt_variation_attribute',
                post_id: $( '#post_ID' ).val(),
                group_id: $( '.alt_variation' ).length,
                var_product_id: 0,
                /*security: woocommerce_admin_meta_boxes_alt_variations.add_variation_nonce*/
            };

            $.post( ajaxurl, data, function( response ) {
                var variation = $( response );
                /*variation.addClass( 'variation-needs-update' );*/

                /*$( '.woocommerce-notice-invalid-variation' ).remove();*/
                $( '#alt_variations_product_data' ).find( '.alt_variations' ).prepend( variation );
                /*$( 'button.cancel-variation-changes, button.save-variation-changes' ).prop( 'disabled', false );*/
                /*$( '#variable_product_options' ).trigger( 'woocommerce_variations_added', 1 );*/
                /*wc_meta_boxes_product_variations_ajax.unblock();*/
            } );

            return false;
        },
        remove_variation_attribute: function( event ) {
            $( event.target ).closest( '.alt_variation' ).remove();
            return false;
        },
        add_variation_product: function( event ) {
            /*wc_meta_boxes_product_variations_ajax.block();*/

            var data = {
                action: 'add_alt_variation_product',
                post_id: $( '#post_ID' ).val(),
                group_id: $( event.target ).closest( '.alt_variation' ).attr( 'id' ),
                var_product_id: $( event.target ).siblings( '.options_group' ).length,
                /*security: woocommerce_admin_meta_boxes_alt_variations.add_variation_nonce*/

            };

            $.post( ajaxurl, data, function( response ) {
                var variation = $( response );
                /*variation.addClass( 'variation-needs-update' );*/

                /*$( '.woocommerce-notice-invalid-variation' ).remove();*/
                $( event.target ).after( variation );
                /*$( 'button.cancel-variation-changes, button.save-variation-changes' ).prop( 'disabled', false );*/
                /*$( '#variable_product_options' ).trigger( 'woocommerce_variations_added', 1 );*/
                /*wc_meta_boxes_product_variations_ajax.unblock();*/
            } );

            return false;
        },
        remove_variation_product: function( event ) {
            $( event.target ).closest( '.options_group' ).remove();
            return false;
        }
    };
    wc_meta_boxes_product_alt_variations_ajax.init();
} );