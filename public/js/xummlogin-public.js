(function( $ ) {
	'use strict';

	$(function() {

		// Hook for the message bubble close
		$('.xl-messages').click(function() {
			$(this).fadeOut();
		});

		// Show/hide username and password form
		$('.toggle_form').click(function(e) {
			e.preventDefault();

			// Add show class to the form
			$('body').toggleClass('xl-display-form');

			// Show/hide the show/hide button, doing it this way for localisation
			$('.toggle_form').toggle();

			// Enable password, somehow WP disables it when loaded hidden
			$('input[type=password]').prop('disabled', false);

			return false;
		})

		// Richlist functionnality
		$('.xl-richlist-tiers tbody tr:first-child').click(function() {

			// Get the group index
			var group_index = $(this).parent().index() - 1;

			// Get wallets based on that index
			show_wallets(group_index);
		});
		
		$('.xl-button-signout a').click(function(e){
			return confirm('Are you sure?');
		});		
	});

	function show_wallets(group_index){
		let tbody = $('.xl-richlist-tiers > tbody')[group_index]; //select group
		let rows  = $(tbody).children('tr'); //select the group rows
		let group = holders[group_index];

		// Load wallet if not already loaded
		if( !group.loaded ){
			let wallet_count = group.wallets.length;
			let output       = '';
			let rank         = 0;
			let balance      = 0;
			let last_balance = 0;

			for (let wallet_index = 0; wallet_index < wallet_count; wallet_index++) {

				// Get the current event object
				let wallet = group.wallets[wallet_index];

				// Get the rank based if there's a new balance or tied with the previous one
				balance = Object.values(wallet)[0];
        rank    = (balance != last_balance) ? wallet_index + 1 : rank;

				output += 
				'<tr>' +
					'<td>' + rank + '</td>' +
					'<td>' + Object.keys(wallet)[0] + '</td>' +
					'<td>' + financial(balance, precision) + ' ' + currency + '</td>' +
				'</tr>';

				// Update last balance
				last_balance = balance;
			}

			// Add to row		
			$(rows[1]).children('td')[0].innerHTML = 
				'<table class="xl-wallets">' + 
					'<tr><th colspan="2">Wallet</th><th>Balance</th></tr>' +
					 output +
				'</table>';

			// Mark as loaded
			group.loaded = true;
		}

		// Toggle row
		$(rows[1]).toggle();
		return;
	}

})( jQuery );

function financial(x, p) {
	let formated_number = Number.parseFloat(x).toFixed(p);
	let tmp = String(formated_number).split('.');

	// Add spaces for thousand sep
	tmp[0] = tmp[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

	return tmp.join('.');
}