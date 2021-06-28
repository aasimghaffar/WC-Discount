(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	 $(document).ready(function()
	 {
		$("input[value].variation_id:hidden").on("change paste keyup", function()
		{
			var id = jQuery(this).val();
			jQuery.ajax({
				type: 'POST',
				url: ajax.url,
				data: {
					action: 'variation_ajax',
					id: id,
					price: jQuery('.discount_filter').val(),
				},
				success: function(response)
				{
					response = JSON.parse(response);
					if(response != '')
					{
						jQuery('.wc_discount_layout_variation .price').html(response.price);
						jQuery('.wc_discount_layout_variation .total_price').html(response.total_price_to_show);
						jQuery('.wc_discount_layout_variation .discount_price').html(response.discount_price);
						jQuery('.discount_filter_price').val(response.total_price);
					}
				},
				error: function() {}
			});
		});
	});

})( jQuery );
