jQuery( function( $ ) {
    $( '#badgeos-set-goals-send-emailing' ).click(function (e) {
        send_emailing(e);
    });
	function send_emailing(e) {
		var value = e.target.value;
        // Unbind event to avoid multiple emailing issue if click again
        $( '#badgeos-set-goals-send-emailing' ).unbind();
		e.target.value = 'in process';
		$.ajax( {
			url: badgeos_set_goals.ajax_url,
			data: {
				'action'  : 'send_emailing',
			},
			dataType : 'json',
			success : function( response ) {
				e.target.value = 'done';
			},
			error : function( response ) {
				e.target.value = 'error';
			}
		});
    }
} );
