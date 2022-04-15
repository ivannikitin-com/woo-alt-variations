jQuery( function( $ ) {
    $( document ).ready( function() {
        var attr_images_wrap = $( ".attr_images_wrap" );
        var var_quantity = $( ".alt_variations_groups_wrap .var_header .var_quantity" );
        var widthSum = 55;
        $( '.attr_images_wrap .woocommerce-product-gallery__image' ).each( function() {
            var that = $( this );
            var visibleWidth = attr_images_wrap.width();
            widthSum = widthSum + 55;
            console.log( 'visibleWidth=' + visibleWidth );
            console.log( 'widthSum=' + widthSum );
            if ( visibleWidth < widthSum ) {
                that.addClass( 'hidden' );
                var_quantity.removeClass( 'hidden' );
            } else {
                that.removeClass( 'hidden' );
                var_quantity.addClass( 'hidden' );
            }
        } );
    } );

    $( window ).on( 'resize', function() {
        var attr_images_wrap = $( ".attr_images_wrap" );
        var var_quantity = $( ".alt_variations_groups_wrap .var_header .var_quantity" );
        var widthSum = 55;
        $( '.attr_images_wrap .woocommerce-product-gallery__image' ).each( function() {
            var that = $( this );
            var visibleWidth = attr_images_wrap.width();
            widthSum = widthSum + 55;
            console.log( 'visibleWidth=' + visibleWidth );
            console.log( 'widthSum=' + widthSum );
            if ( visibleWidth < widthSum ) {
                that.addClass( 'hidden' );
                var_quantity.removeClass( 'hidden' );
            } else {
                that.removeClass( 'hidden' );
                var_quantity.addClass( 'hidden' );
            }
        } );
    } );
} );