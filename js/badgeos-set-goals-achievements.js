jQuery( function( $ ) {
    $('#badgeos-achievements-container').mouseenter(function(){
        $( '.goal-action-img' ).unbind();
        $( '.goal-action-img' ).click(function (e) {
            click_goal_action(e);
        });
    });

	$( '.goal-action-img' ).click(function (e) {
		click_goal_action(e);
	});

	//function to add/delete achievement in goal list
	function click_goal_action(e) {
		var src = e.target.src;
		var srcOrigin;
		var srcTarget;
		var goalToSetImg = "goal-to-set.svg";
		var goalSetImg = "goal-set.svg";
		if ( src.indexOf( goalToSetImg ) != -1 ) {
			srcOrigin = goalToSetImg;
			srcTarget = goalSetImg;
		} else {
			srcOrigin = goalSetImg;
			srcTarget = goalToSetImg;
		}
		e.target.src = src.replace(srcOrigin, 'spinner.gif');
		$.ajax( {
			url: badgeos_set_goals.ajax_url,
			data: {
				'action'  : 'update_goals_on_action',
				'achievement_id': e.target.getAttribute( 'value' ),
				'user_id' : badgeos_set_goals.user_id,
			},
			dataType : 'json',
			success : function( response ) {
				e.target.src = src.replace(srcOrigin, srcTarget);
				if (srcTarget == goalSetImg) {
                    e.target.title = "Click to UNset Goal";
                    e.target.className = "goal-action-img";
                } else {
                    e.target.title = "Click to SET Goal";
                    e.target.className = "goal-action-img transparent";
                }
					// notify user TODO
			},
			error : function( response ) {
				e.target.src = src;
					// notify user TODO
			}
		});
	}	
	
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
							// No need of load more button for goals
							$( '#goals_list_load_more' ).hide();
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
