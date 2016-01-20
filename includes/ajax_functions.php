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

$badgeos_ajax_actions = array(
    'update_goals_on_action',
);
// Register core Ajax calls.
foreach ( $badgeos_ajax_actions as $action ) {
	add_action( 'wp_ajax_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
	add_action( 'wp_ajax_nopriv_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
}

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
