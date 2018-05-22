jQuery( function( $ ) {
	$( '#go-recent-tweets-clear-cache' ).click( function() {
		var data = {
			'action': 'go_recent_tweets_clear_cache'
		};

		$.post( ajaxurl, data, function( response ) {
			alert( response );
		} );
	} );
} );
