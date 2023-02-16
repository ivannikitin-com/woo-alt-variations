jQuery( function( $ ) {
    function show_attr_images_on_category() {
        $( '.product' ).find( '.attr_images_wrap' ).each( function() {
            var attr_images_wrap = $( this );
            var var_quantity = $( this ).find( ".var_quantity" );
            var widthSum = 55;
            $( this ).find( '.woocommerce-product-gallery__image' ).each( function() {
                var that = $( this );
                var visibleWidth = attr_images_wrap.width();
                widthSum = widthSum + 23 + 3;
                if ( visibleWidth < widthSum && $( this ).is( ':last-child' ) == false ) {
                    that.addClass( 'hidden' );
                    var_quantity.removeClass( 'hidden' );
                } else {
                    that.removeClass( 'hidden' );
                    var_quantity.addClass( 'hidden' );
                }
            } );
        } );
    }

    function show_attr_images_on_product() {
        $( '.var_header' ).find( '.attr_images_wrap' ).each( function() {
            var attr_images_wrap = $( this );
            var var_quantity = $( this ).find( ".var_quantity" );
            var widthSum = 55;
            $( this ).find( '.woocommerce-product-gallery__image' ).each( function() {
                var that = $( this );
                var visibleWidth = attr_images_wrap.width();
                widthSum = widthSum + 55;
                if ( visibleWidth < widthSum && $( this ).is( ':last-child' ) == false ) {
                    that.addClass( 'hidden' );
                    var_quantity.removeClass( 'hidden' );
                } else {
                    that.removeClass( 'hidden' );
                    var_quantity.addClass( 'hidden' );
                }
            } );
        } );
    }
    $( document ).ready( function() {
        show_attr_images_on_category();
        show_attr_images_on_product();
    } );

/*    $( '#tab-like-product' ).imagesLoaded( function( instance ) {
        console.log( '#tab-like-product imagesLoaded done' );
        show_attr_images_on_category();
    } );*/

    $( document ).on( 'click', '#lmp_v2_btn', function() {
        setTimeout( function() {
            show_attr_images_on_category();
            show_attr_images_on_product();
        }, 500 );
    } );

    $( window ).on( 'resize', function() {
        show_attr_images_on_category();
        show_attr_images_on_product();
    } );
} );