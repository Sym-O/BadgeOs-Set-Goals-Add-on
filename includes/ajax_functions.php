<?php
/**
* Add goal achievements feature to BadgeOS plugin
*
* @package BadgeOS D2SI
* @subpackage Achievements
* @author D2SI
* @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
* @link https://D2SI.fr
 */


/**
 * AJAX Helper for updating user's goals
 *
 * @return void
 */
function badgeos_ajax_update_goals_on_action() {
    global $user_ID;

    $achievement_id = isset( $_REQUEST['achievement_id'] ) ? $_REQUEST['achievement_id']  : -1;
    $user_id        = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id']  : -1;

    if ( $user_id != -1 )
        $user_id = $user_ID;

    $achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $achievement_id ) );
    
    
    if (! $achievements ) {
        // achievement is not in user achievements, we flip the goal status
        if (count(badgeos_get_user_goals( $user_id, $achievement_id ))==0) {
            // Not in goals, we add the new goal
            badgeos_set_new_goal( $user_id, $achievement_id );
            wp_send_json_success( array(
		        'message'     => 'Add goal: achievement_id:'.$achievement_id. ' user_id:'.$user_id,
        	) );
        }
        else {
            // In goals, we remove the goal
            badgeos_remove_goal( $user_id, $achievement_id );
            wp_send_json_success( array(
		        'message'     => 'Remove goal: achievement_id:'.$achievement_id. ' user_id:'.$user_id. ' count:'.count(badgeos_get_user_goals( $user_id, $achievement_id )),
        	) );
        }
    }
//    wp_send_json_error($this->directory_url . 'Oups:'.$user_id.' '.$achievement_id );
}

 /* AJAX Helper for returning achievements
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_set_goals_get_achievements() {
	global $user_ID, $blog_id;

	// Setup our AJAX query vars
	$type       = isset( $_REQUEST['type'] )       ? $_REQUEST['type']       : false;
	$limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
	$offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
	$count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
	$filter     = isset( $_REQUEST['filter'] )     ? $_REQUEST['filter']     : false;
	$search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
	$user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : false;
	$orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : false;
	$order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : false;
	$wpms       = isset( $_REQUEST['wpms'] )       ? $_REQUEST['wpms']       : false;
	$include    = isset( $_REQUEST['include'] )    ? $_REQUEST['include']    : array();
	$exclude    = isset( $_REQUEST['exclude'] )    ? $_REQUEST['exclude']    : array();
	$meta_key   = isset( $_REQUEST['meta_key'] )   ? $_REQUEST['meta_key']   : '';
	$meta_value = isset( $_REQUEST['meta_value'] ) ? $_REQUEST['meta_value'] : '';
    $layout     = isset( $_REQUEST['layout'] )     ? $_REQUEST['layout']     : 'list';
	$tag        = isset( $_REQUEST['tag'] )        ? $_REQUEST['tag']        : false;
    $show_goals = isset( $_REQUEST['show_goals'] ) ? $_REQUEST['show_goals'] : false;

	// Convert $type to properly support multiple achievement types
	if ( 'all' == $type ) {
		$type = badgeos_get_achievement_types_slugs();
		// Drop steps from our list of "all" achievements
		$step_key = array_search( 'step', $type );
		if ( $step_key )
			unset( $type[$step_key] );
	} else {
		$type = explode( ',', $type );
	}

	// Get the current user if one wasn't specified
	if( ! $user_id )
		$user_id = $user_ID;

	// Build $include array
	if ( !is_array( $include ) ) {
		$include = explode( ',', $include );
	}

	// Build $exclude array
	if ( !is_array( $exclude ) ) {
		$exclude = explode( ',', $exclude );
	}

    // Initialize our output and counters
    $achievements = '';
    $achievement_count = 0;
    $query_count = 0;

    // Grab our hidden badges (used to filter the query)
	$hidden = badgeos_get_hidden_achievement_ids( $type );

	// If we're polling all sites, grab an array of site IDs
	if( $wpms && $wpms != 'false' )
		$sites = badgeos_get_network_site_ids();
	// Otherwise, use only the current site
	else
		$sites = array( $blog_id );

	// Loop through each site (default is current site only)
	foreach( $sites as $site_blog_id ) {

		// If we're not polling the current site, switch to the site we're polling
		if ( $blog_id != $site_blog_id ) {
			switch_to_blog( $site_blog_id );
		}

		// Grab our earned badges (used to filter the query)
		$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );

		// Query Achievements
		$args = array(
			'post_type'      =>	$type,
			'orderby'        =>	$orderby,
			'order'          =>	$order,
			'posts_per_page' =>	$limit,
			'offset'         => $offset,
			'post_status'    => 'publish',
			'post__not_in'   => array_diff( $hidden, $earned_ids )
		);

		// Filter - query completed or non completed achievements
		if ( $filter == 'completed' ) {
			$args[ 'post__in' ] = array_merge( array( 0 ), $earned_ids );
		}elseif( $filter == 'not-completed' ) {
			$args[ 'post__not_in' ] = array_merge( $hidden, $earned_ids );
		}

		if ( '' !== $meta_key && '' !== $meta_value ) {
			$args[ 'meta_key' ] = $meta_key;
			$args[ 'meta_value' ] = $meta_value;
		}

		// Include certain achievements
		if ( !empty( $include ) ) {
			$args[ 'post__not_in' ] = array_diff( $args[ 'post__not_in' ], $include );
			$args[ 'post__in' ] = array_merge( array( 0 ), array_diff( $include, $args[ 'post__in' ] ) );
		}

		// Exclude certain achievements
		if ( !empty( $exclude ) ) {
			$args[ 'post__not_in' ] = array_merge( $args[ 'post__not_in' ], $exclude );
		}

		// Search
		if ( $search ) {
			$args[ 's' ] = $search;
		}
        // Layout filter
        if ( 'grid' == $layout ) {
            add_action( 'badgeos_render_achievement', 'badgeos_grid_render_achievement', 10, 2 );
        }
 
        // Tag Filter
        if ( 'all'!== $tag ) {
		    $tag = explode( ',', $tag );
            $args[ 'tag__in' ] = $tag;
        }

        // Get user's goals & Make sure our script is loader
        $goals_array =  badgeos_get_user_goals( $user_id );
        wp_enqueue_script( 'badgeos-set-goals-achievements' );
        // DEBUG $achievements .= "GOALS: ".join("/",$goals_array).' // Show goals only '.$show_goals;//TODO

		// Loop Achievements
		$achievement_posts = new WP_Query( $args );
		$query_count += ($show_goals === "true")? count($goals_array) : $achievement_posts->found_posts;
		while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();
                $achievements .= badgeos_set_goals_filter(get_the_ID(), badgeos_render_achievement(get_the_ID()), $show_goals, $goals_array, $layout); 
                $achievement_count++;
		endwhile;

		// Sanity helper: if we're filtering for complete and we have no
		// earned achievements, $achievement_posts should definitely be false
		/*if ( 'completed' == $filter && empty( $earned_ids ) )
			$achievements = '';*/

		// Display a message for no results
		if ( empty( $achievements ) ) {
			// If we have exactly one achivement type, get its plural name, otherwise use "achievements"
			$post_type_plural = ( 1 == count( $type ) ) ? get_post_type_object( current( $type ) )->labels->name : __( 'achievements' , 'badgeos' );

			// Setup our completion message
			$achievements .= '<div class="badgeos-no-results">';
			if ( 'completed' == $filter ) {
				$achievements .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}else{
				$achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}
			$achievements .= '</div><!-- .badgeos-no-results -->';
		}

		if ( $blog_id != $site_blog_id ) {
			// Come back to current blog
			restore_current_blog();
		}

	}
    
	// Send back our successful response
	wp_send_json_success( array(
		'message'     => $achievements,
		'offset'      => $offset + $limit,
		'query_count' => $query_count,
		'badge_count' => $achievement_count,
		'type'        => $type,
	) );
}
