<?php
/**
* Add goal achievements feature to BadgeOS plugin
*
* @package BadgeOS D2SI
* @subpackage Achievements
* @author D2SI
* @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
* @link https://d2-si.fr
 */

/**
* Return an array of the goals that match the arguments
*
*/
function badgeos_set_goals_build_email( $user_id ) {
    $email = array(
        'object' => '[Adopteunbadge] Petite mise au point sur tes objectifs de progression !',
        'message' => ''
    );
    $user_info      = get_userdata($user_id);
    $goals_array    = badgeos_get_user_goals( $user_id );
    $badgeos_settings = get_option( 'badgeos_settings' );
	
    if ( count( $goals_array ) == 0 ) {
        $message            = $badgeos_settings['goals_emailing_no_goal'];
    } else {
        $message            = $badgeos_settings['goals_emailing_goals'];
        $goals_html = "<center><p>";
        $goals_count = 0;
	    foreach ( $goals_array as $goal ) {
	    	$goals_html .= "&nbsp;&nbsp;&nbsp;<a href='".get_permalink($goal)."'>" . badgeos_get_achievement_post_thumbnail($goal)."</a>";
            $goals_count ++;
            if ($goals_count%5 == 0) {
                $goals_html .= "</p><p>\r\n";
            }
	    }
        $goals_html .= "</p></center>";
        $message = str_replace("[goals]",$goals_html, $message);
    }
    $message            = str_replace("[name]",$user_info->first_name, $message);
    $message            = str_replace("[custom-message]",$badgeos_settings['goals_notification_custom_message'], $message);
    $email['message']   = $message;

    return $email;
}

/**
* Send notification on goals to all users 
* TODO : remove user filtering
* TODO : handle error case in emailing to let ajax call alert admin user
**/
function badgeos_set_goals_send_notifications() {
	$users = get_users();
    add_filter('wp_mail_content_type','set_html_content_type');
	foreach ( $users as $user ) {
		$recipient = esc_html( $user->user_email );
            $email = badgeos_set_goals_build_email ($user->id);
            wp_mail( $recipient, $email['object'], wordwrap($email['message']) );
    }
    remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
}

/**
* Update user meta goals field based on an array
*
* 
*/
function badgeos_update_user_goals_meta ( $user_id, $goals_array ) {
    if ( count($goals_array) == 0 )
        $goals =  "";
    else
        $goals = join( " ", $goals_array );
    //Update user meta
    if ( $achievement_id == -1 and ! current_user_can( 'edit_user', $user_id ))
        wp_send_json_error('something unexpected happened');
    else 
        update_usermeta( $user_id, '_badgeos_goals', $goals );
}

/**
 * Check if there is only correct value in the user goals list
 * Correct things if needed and return the clean array
 */
function badgeos_check_user_goals ( $user_id, $goals_array = 0 ) {
    if (!$goals_array) {
        $goals = get_user_meta( $user_id, '_badgeos_goals', true );
        if($goals == "") {
            return array();
        } else {
            $goals_array = array_map('intval', explode(" ", $goals ) );
        }
    }

    $is_corrected = 0;
    for ( $i=0 ; $i <=count($goals_array)-1 ; $i++ ) {
        if ( get_post_status($goals_array[$i]) != 'publish' || ! badgeos_is_achievement($goals_array[$i]) ) {
            unset($goals_array[$i]);
            $is_corrected = 1;
        }
    }
    if ($is_corrected) {
        badgeos_update_user_goals_meta($user_id, $goals_array);
    }
    return $goals_array;
}


/**
* Return an array of the goals that match the arguments
*
* 
*/
function badgeos_get_user_goals ( $user_id, $achievement_id = 0 ) {

	// Grab the user's current goals
    $goals = get_user_meta( $user_id, '_badgeos_goals', true );
    if($goals == "") {
        return array();
    }
    else {
        $goals_array = array_map('intval', explode(" ", $goals ) );
        if ( $achievement_id != 0 ){
            if ( in_array($achievement_id, $goals_array) ) {
                return array ($achievement_id);
            }
            else {
                return array();
            }
        }
        else {
            return badgeos_check_user_goals($user_id, $goals_array);
        }
    }
}

/**
*   add a new achievement to the goals of user_id
*
* 
*/
function badgeos_set_new_goal ( $user_id, $achievement_id ){
    $goals_array = badgeos_get_user_goals( $user_id );
    if ( ! in_array($achievement_id, $goals_array) ) {
        array_push( $goals_array, $achievement_id );
        badgeos_update_user_goals_meta($user_id, $goals_array);
        // Log the action
        $user = get_userdata( $user_id );
        $title = sprintf( __( '%1$s set goal on %2$s', 'badgeos' ),
            $user->user_login,
            get_the_title( $achievement_id )
        );
        badgeos_post_log_entry( $achievement_id, $user_id, null, $title );
    }
}

/**
*   Remove an achievement form the goals of user_id
*
* 
*/
function badgeos_remove_goal ( $user_id, $achievement_id, $log_message = null ){
    $goals_array = badgeos_get_user_goals( $user_id );
    if (count($goals_array)>0 && in_array($achievement_id, $goals_array)){ 
        $key = array_search( $achievement_id, $goals_array );
        unset($goals_array[$key]);
        badgeos_update_user_goals_meta($user_id, $goals_array);
        if ($log_message) {
            // if log message is set we log the action
            $user = get_userdata( $user_id );
            $title = sprintf( __( '%1$s %2$s %3$s', 'badgeos' ),
                $user->user_login,
                $log_message,
                get_the_title( $achievement_id )
            );
            badgeos_post_log_entry( $achievement_id, $user_id, null, $title );
        }
    }
}

/**
 * Build html code for goal button
 *
 * @since  1.0.0
 * @param  string $content The page content
 * @return string          The page content after reformat
 */
function badgeos_set_goals_build_button( $class, $in_goals = false ) {
    $achievement_id = get_the_ID();
    $button ='';

    if ($in_goals) {
        $button = '<div class="'.$class.'"><img class="goal-action-img" value="'.$achievement_id.'" src="'.badgeos_set_goals_get_directory_url().'/images/goal-set.svg" title="Click to UNset Goal"></img></div>';
    } else {
        $button = '<div class="'.$class.'"><img class="goal-action-img transparent" value="'.$achievement_id.'" src="'.badgeos_set_goals_get_directory_url().'/images/goal-to-set.svg" title="Click to SET Goal"></img></div>';
    }
    return $button;
}

 /* AJAX Helper for inserting goals elements in achievement rendering
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_set_goals_filter($achievement_html, $achievement_id, $goals_array = 0){
    global $user_ID;

    $show_goals = isset( $_REQUEST['show_goals'] ) ? $_REQUEST['show_goals'] : false;
    $layout     = isset( $_REQUEST['layout'] )     ? $_REQUEST['layout']     : 'list';

    $goals_array = ( $goals_array == 0 ) ? badgeos_get_user_goals( $user_ID ) : $goals_array;
    $in_goals = in_array( get_the_ID() , $goals_array );

    if ( $show_goals === "true" && !$in_goals ) {
        // Skip achievement because we want to display only goals
        return "";
    }
    else {
        // build button
        $button = badgeos_set_goals_build_button("goal-action", $in_goals);
        
        // Add button depending on layout
        if ($layout == "list"){
            $achievement_html = str_replace("<!-- .badgeos-item-image -->","<!-- .badgeos-item-image -->".$button, $achievement_html);
            return $achievement_html;
        }
        else {
            $button = '<div class="goal-action-container">'.$button;
            $achievement_html = str_replace('<div class="badgeos-item-image">',$button.'<div class="badgeos-item-image">', $achievement_html);
            $achievement_html = str_replace("<!-- .badgeos-item-image -->","</div><!-- .badgeos-item-image -->", $achievement_html);
            return $achievement_html;
        }
    }
}
add_action( 'badgeos_render_achievement', 'badgeos_set_goals_filter', 10, 2);

/**
 * Filter title content to add a goal button
 *
 * @since  1.0.0
 * @param  string $content The page content
 * @return string          The page content after reformat
 */
function badgeos_set_goals_achievement_wrap_filter( $content ) {

    //$main_title = single_post_title('',false);
	//if ( $content && $main_title && strcmp($content, $main_title )!== 0 )
	//	return $content;

	$id = get_the_ID();	

    if ( !badgeos_is_achievement( $id ) )
        return $content;
	// filter, but only on the main loop!
	if ( !badgeos_is_main_loop( $id ) )
		return $content;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-set-goals-achievements' );

	global $user_ID;
	
    $goals_array = badgeos_get_user_goals( $user_ID );
    $in_goals = in_array( $id , $goals_array );

    // build button
    $button = badgeos_set_goals_build_button("goal-action", $in_goals);


	// now that we're where we want to be, tell the filters to stop removing
	$GLOBALS['badgeos_reformat_content'] = true;

	$newcontent = $button.$content ;
    //$newcontent = str_replace('<h1 class="entry-title">',$button.'<h1 class="entry-title">', $content);
	
	// Ok, we're done reformating
	$GLOBALS['badgeos_reformat_content'] = false;

	return $newcontent;
}
add_filter( 'the_content', 'badgeos_set_goals_achievement_wrap_filter', 9 );

/**
* delete given achievement id from aimed achievments list if present
*
* 
*/
function badgeos_set_goals_update_goals_on_award( $user_id, $achievement_id ) {
    badgeos_remove_goal( $user_id, $achievement_id, "achieve goal" );
}
add_action( 'badgeos_award_achievement', 'badgeos_set_goals_update_goals_on_award', 10, 2);

//////  ADMIN FUNCTIONS ///
/**
*
* 
*/
function badgeos_set_goals_get_user_data( $args = null ) {

	global $wpdb;

	$defaults = array(
		'user_id'          => 0,     // The given user's ID
		'site_id'          => get_current_blog_id(), // The given site's ID
		'achievement_id'   => false, // A specific achievement's post ID
		'achievement_type' => false, // A specific achievement type
		'since'            => 0,     // A specific timestamp to use in place of $limit_in_days
	);
	$args = wp_parse_args( $args, $defaults );

	// get all goals set by the user
	$goals_user = ( $goals_set = get_user_meta( absint( $args['user_id'] ), '_badgeos_goals', true ) ) ? (array) $goals_set : array();
	$goals_id = explode( " ", $goals_user[0] );
	$goals = array();

	// prepare informations for the admin's user page information
	if ( !empty( $goals_id ) ) {

		foreach ( $goals_id as $goal_id ) {
			$post = get_post( $goal_id );
			$goals[] = array(
								"post_info" => $post,
							    "goal_date" => $wpdb->get_var( $wpdb->prepare( 
																					"
																						SELECT MAX(post_date) 
																						FROM wp_posts
																						INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id 
																						WHERE wp_postmeta.meta_value = %d
																						AND wp_posts.post_author = %d
																					", 
																					$post->ID,
																					$args['user_id']
																				)
							    								)
							    );
		}

	}

    return $goals;

}


/**
*
* 
*/
function badgeos_set_goals_user_profile_data( $user = null ) {

		$goals = array();
        $goals = badgeos_set_goals_get_user_data( array( 'user_id' => absint( $user->ID ) ) );

        // List all of a user's setted goals
        if ( !empty( $goals ) ) {

        	echo '<h2>' . __( 'List of Goals set', 'badgeos' ) . '</h2>';

	        echo '<table class="form-table">';
	        echo '<tr><td colspan="2">';

            echo '<table class="widefat badgeos-table">';
            echo '<thead><tr>';
                echo '<th>'. __( 'Image', 'badgeos' ) .'</th>';
                echo '<th>'. __( 'Name', 'badgeos' ) .'</th>';
                echo '<th>'. __( 'Date', 'badgeos' ) .'</th>';
            echo '</tr></thead>';

            foreach ( $goals as $goal ) {

                echo '<tr>';
                    echo '<td>'. badgeos_get_achievement_post_thumbnail( $goal[post_info]->ID, array( 50, 50 ) ) .'</td>';
                    echo '<td>'. $goal[post_info]->post_title .' </td>';
                    echo '<td>'. $goal[goal_date] .' </td>';
                echo '</tr>';

            }

            echo '</table>';
            echo '</td></tr>';
    	    echo '</table>';
        }
        
}
add_action( 'show_user_profile', 'badgeos_set_goals_user_profile_data' );

///*
// *  ADMIN functions
// *  TODO : clean and improve
// *
// */
//// template to generate a my_badges page
//function my_badges() {
//
//    global $user_ID;
//
//    echo "<h3>My aimed badges</h3>";
//
//    if ( get_the_author_meta('aimed_badges', $user_ID) )
//        echo get_the_author_meta('aimed_badges', $user_ID);
//    else
//        echo "None";
//}
//
//// used in admin/user-profile
//function show_aimed_badges ( $user ) {
//
//    $goals = esc_attr( get_user_meta($user->ID, '_badgeos_goals', true));
//
//    echo '
//        <h3>Aimed badges</h3>
//        <div>
//            <input type="text" name="goals" id="goals" value="'.$goals.'" class="regular-text" /><br />
//            <span class="description"> The goals you set for you ! </span>
//        </div>';
//
//    my_badges();
//}
//add_action( 'show_user_profile', 'show_aimed_badges' );
//
//
//// save badge from admin user modification
//function save_aimed_badges( $user_id ) {
//
//    if ( ! current_user_can( 'edit_user', $user_id ) )
//        return false;
//    // explode aimed and chek badge one by one
//
//    update_usermeta( $user_id, '_badgeos_goals', $_POST['goals'] );
//}
//add_action( 'edit_user_profile', 'save_aimed_badges' );
//add_action( 'edit_user_profile_update', 'save_aimed_badges' );
//add_action( 'personal_options_update', 'save_aimed_badges' );
