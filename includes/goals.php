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

define('BADGEOS_SET_GOALS_NOTIFY', false);

/**
* Return an array of the goals that match the arguments
*
* TODO: Enable setting of the message sent in the parameter section of the plugin, not in the source code just below 
* TODO: Add multi language support
*/
function badgeos_set_goals_build_email( $user_id ) {
    $email = array(
        'object' => '[Adopteunbadge] Petite mise au point sur tes objectifs de progression !',
    );
    $user_info      = get_userdata($user_id);
    $goals_array    = badgeos_get_user_goals( $user_id );
	
    if ( count( $goals_array ) == 0 ) {
        $email['message'] .= "<p>Bonjour ".$user_info->first_name." !</p>
            <p>Il semblerait que tu n'aies aucun objectif de progression fixé sur Adopteunbadge. Cette application est là pour t'aider dans l'acquisition de nouvelles compétences essentielles pour ton profil. Elle sert de support dans ta relation avec ton RH et, si tu en as un, ton parrain technique.</p>
            <p>Pour bénéficier de cet outil, connecte-toi sur <a href='http://adopteunbadge.d2-si.fr'>adopteunbadge.d2-si.eu</a> et commence par te fixer tes propres objectifs de progression !</p>";
	    $email['message'] .= "<p>À bientôt sur Adopteunbadge</p>";
    } else {
        $email['message'] .= "<p>Bonjour ".$user_info->first_name." !</p>
            <p>Voici un rappel de tes objectifs de progression sur Adopteunbadge. Où en es-tu ? Est-ce que tu as des difficultés à passer certaines étapes ? C'est le bon moment pour faire un bilan et contacter ton RH si tu souhaites en discuter !</p>";
	    foreach ( $goals_array as $goal ) {
	    	$email['message'] .= "&nbsp;&nbsp;&nbsp;<a href='".get_permalink($goal)."'>" . badgeos_get_achievement_post_thumbnail($goal)."</a>";
	    }
	    $email['message'] .= "<p>Pour gérer tes objectifs ou être accompagné(e) dans leur obtention, connecte-toi sur <a href='http://adopteunbadge.d2-si.fr'>adopteunbadge.d2-si.eu</a> !</p>";
	    $email['message'] .= "<p>À bientôt sur Adopteunbadge</p>";
    }

    return $email;
}

/**
* This is the function that is executed by the monthly recurring
* action badgeos_set_goals_task_hook defined in bageos-set-goals-add-on.php
**/
function badgeos_set_goals_send_notifications() {
	$users = get_users();
    add_filter('wp_mail_content_type','set_html_content_type');
	foreach ( $users as $user ) {
		$recipient = esc_html( $user->user_email );
        if ( BADGEOS_SET_GOALS_NOTIFY ) {
            $email = badgeos_set_goals_build_email ($user->id);
            wp_mail( $recipient, $email['object'], $email['message'] );
        } 
    }
    remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
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
