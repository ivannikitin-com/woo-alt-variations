jQuery( function( $ ) {

    $( document ).ready( function() {
        $( ".modal" ).on( "shown.bs.modal", function() {
            var urlReplace = "#" + $( this ).attr( 'id' );
            history.pushState( null, null, urlReplace );
        } );
        $( '#myModal' ).on( 'hide.bs.modal', function( e ) {
            if ( location.hash == '#modal' ) window.history.back();
        } );

        $( ".modal" ).on( "hidden.bs.modal", function() { history.back( 1 ); } );

        /*        $( window ).on( 'popstate', function( event ) { //pressed back button
                    if ( event.state !== null ) $( '.modal' ).modal( 'hide' );
                } );*/
    } );


    $( document ).on( 'click', ".upload_image_button", function( event ) {
        upload_button = $( this );
        var frame;
        event.preventDefault();
        if ( frame ) {
            frame.open();
            return;
        }
        frame = wp.media();
        frame.on( "select", function() {
            // Grab the selected attachment.
            var attachment = frame.state().get( "selection" ).first();
            var attachmentUrl = attachment.attributes.url;
            attachmentUrl = attachmentUrl.replace( '-scaled.', '.' );
            frame.close();
            upload_button.find( 'img' ).attr( 'src', attachmentUrl );
            upload_button.find( 'input.image_id' ).val( attachment.attributes.id );
            /*            $( ".zci-taxonomy-image" ).attr( "src", attachmentUrl );
                        if ( upload_button.parent().prev().children().hasClass( "tax_list" ) ) {
                            upload_button.parent().prev().children().val( attachmentUrl );
                            upload_button.parent().prev().prev().children().attr( "src", attachmentUrl );
                        } else
                            $( "#zci_taxonomy_image" ).val( attachmentUrl );*/
        } );
        frame.open();
    } );
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
                var_product_id: $( event.target ).siblings( '.sortable_wrap' ).children( '.options_group' ).length
                /*security: woocommerce_admin_meta_boxes_alt_variations.add_variation_nonce*/
            };

            $.post( ajaxurl, data, function( response ) {
                var variation = $( response );
                /*variation.addClass( 'variation-needs-update' );*/

                /*$( '.woocommerce-notice-invalid-variation' ).remove();*/
                $( event.target ).siblings( '.sortable_wrap' ).prepend( variation );
                //$( 'select.wc-product-search' ).selectWoo( select2_args ).addClass( 'enhanced' );
                // Ajax product search box
                $( document.body ).trigger( 'wc-enhanced-select-init' );
                $( document.body ).on( 'change', '.wc-product-search', function() {
                    $( document.body ).trigger( 'wc-enhanced-select-init' );
                } );

                //$( document.body ).trigger( 'wc-enhanced-select-init' );
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


    $( this ).on( 'change', '.wc-product-search', function() {
        /*        if ( ! $( this ).closest( 'tr' ).is( ':last-child' ) ) {
                    return;
                }*/
        $( document.body ).trigger( 'wc-enhanced-select-init' );
    } );

    $( ".woocommerce_variable_attributes .sortable_wrap" ).sortable();
} );