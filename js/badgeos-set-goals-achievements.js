jQuery( function( $ ) {
    $('#badgeos-achievements-container').mouseenter(function(){
        $( '.goal-action-img' ).unbind();
        $( '.goal-action-img' ).click(function (e) {
            $.ajax( {
                url: badgeos_set_goals.ajax_url,
                data: {
                    'action'  : 'update_goals_on_action',
                    'achievement_id': this.getAttribute( 'value' ),
                    'user_id' : badgeos_set_goals.user_id,
                },
                dataType : 'json',
                success : function( response ) {
                    var src = e.target.src;
                    if ( src.indexOf( "goal-to-set.png" ) != -1 )
                        e.target.src = src.replace('goal-to-set.png', 'goal-set.png');
                    else {
                        e.target.src = src.replace('goal-set.png', 'goal-to-set.png');
                        // notify user TODO
                    }
                }
            });
        });
    });

	// Our main achievement list AJAX call
	function badgeos_ajax_goals_list() {
		$.ajax( {
					url : badgeos.ajax_url,
					data : {
						'action' : 'get-achievements',
						'type' : badgeos.type,
						'limit' : badgeos.limit,
						'show_parent' : badgeos.show_parent,
						'show_child' : badgeos.show_child,
						'group_id' : badgeos.group_id,
						'user_id' : badgeos.user_id,
						'wpms' : badgeos.wpms,
						'offset' : $( '#badgeos_achievements_offset' ).val(),
						'count' : $( '#badgeos_achievements_count' ).val(),
						'filter' : $( '#goals_list_filter' ).val(),
						'search' : $( '#achievements_list_search' ).val(),
						'orderby' : badgeos.orderby,
						'order' : badgeos.order,
						'include' : badgeos.include,
						'exclude' : badgeos.exclude,
						'meta_key' : badgeos.meta_key,
						'meta_value' : badgeos.meta_value,
                        'layout' : badgeos.layout,
						'tag' : badgeos.tag,
                        'show_goals' : badgeos.show_goals,
					},
					dataType : 'json',
					success : function( response ) {
						$( '.badgeos-spinner' ).hide();
						if ( response.data.message === null ) {
							//alert("That's all folks!");
						}
						else {
							$( '#badgeos-achievements-container' ).append( response.data.message );
							$( '#badgeos_achievements_offset' ).val( response.data.offset );
							$( '#badgeos_achievements_count' ).val( response.data.badge_count );
							//credlyize();
							//hide/show load more button
							if ( $( '.badgeos-achievements-grid-item' ).length < 30 ) {
								$( '#goals_list_load_more' ).hide();
							}
							else {
								$( '#badgeos-achievements-container' ).height( Math.ceil(($( '.badgeos-achievements-grid-item' ).length / 5))*200);
                                $( '#goals_list_load_more' ).show();
							}
						}
					}
				} );

	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_goals_list_reset() {

		$( '#badgeos_achievements_offset' ).val( 0 );
		$( '#badgeos_achievements_count' ).val( 0 );

		$( '#badgeos-achievements-container' ).html( '' );
		$( '#goals_list_load_more' ).hide();

		badgeos_ajax_goals_list();

	}

	// Listen for changes to the achievement filter
	$( '#goals_list_filter' ).change(function() {

		badgeos_ajax_goals_list_reset();

	} ).change();

	// Listen for search queries
	$( '#goals_list_search_go_form' ).submit( function( event ) {

		event.preventDefault();

		badgeos_ajax_goals_list_reset();

	} );

	// Listen for users clicking the "Load More" button
	$( '#goals_list_load_more' ).click( function() {

		$( '.badgeos-spinner' ).show();
		badgeos_ajax_goals_list();

	} );
} );
