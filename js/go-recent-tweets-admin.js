jQuery( function( $ ) {
	$( '#go_recent_tweets_clear_cache' ).click( function() {
		var data = {
			'action': 'go_recent_tweets_clear_cache'
		};

		$.post( ajaxurl, data, function( response ) {
			alert( response );
		} );
	} );
} );
