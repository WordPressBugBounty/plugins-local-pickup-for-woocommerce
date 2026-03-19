(function( $ ) {
	'use strict';

	/**
	 * All of the code for admin-facing Import Export JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 */

    $(document).ready(function(){
        
        function hideActions(){
            console.log($('.wc-shipping-zone-method-rows tr[data-id="0"]'));
            $('.wc-shipping-zone-method-rows tr[data-id="0"]').each(function(){
                $(this).find('.wc-shipping-zone-actions div').remove();
            });
        }

        // run repeatedly (covers AJAX reload)
        setInterval(hideActions, 200);
    });
    
})( jQuery );