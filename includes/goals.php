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
            return $goals_array;
        }
    }
}

/**
*   add a new achievement to the goals of user_id
*
* 
*/
function badgeos_set_new_goal ( $user_id, $achievement_id ){
    if ( !badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $achievement_id ))){
        $goals_array = badgeos_get_user_goals( $user_id );
        if ( ! in_array($achievement_id, $goals_array) ) {
            array_push( $goals_array, $achievement_id );
            $goals = join( " ", $goals_array );
            // Update user meta
            if ( $achievement_id == -1 and ! current_user_can( 'edit_user', $user_id ))
                wp_send_json_error('something unexpected happened');
            else {
                update_usermeta( $user_id, '_badgeos_goals', $goals );
            }
        }
    }
}

/**
*   Remove an achievement form the goals of user_id
*
* 
*/
function badgeos_remove_goal ( $user_id, $achievement_id ){
    $goals_array = badgeos_get_user_goals( $user_id );
    if (count($goals_array)>0 && in_array($achievement_id, $goals_array)){ 
        $key = array_search( $achievement_id, $goals_array );
        unset($goals_array[$key]);
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
        $achieved = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => $achievement_id) );

        // build button
        $button = '';
        if ($achieved && $in_goals)
            $button = '<div class="goal-action"><img  class="goal-no-action" value="'.$achievement_id.'" src="'.badgeos_set_goals_get_directory_url().'/images/goal-success.png"></img></div>';
        else if (!$achieved && $in_goals)
            $button = '<div class="goal-action"><img class="goal-action-img" value="'.$achievement_id.'" src="'.badgeos_set_goals_get_directory_url().'/images/goal-set.png"></img>    </div>';
        else if (!$achieved && !$in_goals)
            $button = '<div class="goal-action"><img class="goal-action-img" value="'.$achievement_id.'" src="'.badgeos_set_goals_get_directory_url().'/images/goal-to-set.png"></img> </div>';
        
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
* delete given achievement id from aimed achievments list if present
*
* 
*/
function badgeos_set_goals_update_goals_on_award( $user_id, $achievement_id ) {
    // For now, no need to remove them
    //bageos_remove_goal( $user_id, $achievement_id );
}
//add_action( 'badgeos_award_achievement', 'badgeos_set_goals_update_goals_on_award', 10, 2);

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
