(function( $ ) {
	'use strict';

	$(function() {

		// Hook for the clear active voting
		$('#clear-active-voting').click(function() {

			// Get confirmation
			var user_confirmation = confirm('Are you sure you want to clear the current active voting?');
			
			// If the user agrees, clear the form and submit it
			if( user_confirmation ){

				// Clear the fields
				$(this).closest('form').find('.form-table :input').not(':button, :submit, :reset, :checkbox, :radio').val('');
				$(this).closest('form').find('.form-table :checkbox, :radio').prop('checked', false);

				// Submit the form to save/clear the settings
				$(this).closest('form').find(':submit').trigger('click');
			}
		});

	});

})( jQuery );
