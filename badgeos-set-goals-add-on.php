<?php
/**
 * Plugin Name: BadgeOS Set Goals Add-On
 * Plugin URI: http://www.d2-si.fr/
 * Description: This BadgeOS add-on enable "set goals" features
 * Author: D2SI
 * Version: 1.0.0
 * Author URI: https://www.d2-si.fr/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Our main plugin instantiation class
 *
 * This contains important things that our relevant to
 * our add-on running correctly. Things like registering
 * custom post types, taxonomies, posts-to-posts
 * relationships, and the like.
 *
 * @since 1.0.0
 */
class BadgeOS_Set_Goals {

	/**
	 * Get everything running.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		// Load translations : no need for now
		// load_plugin_textdomain( 'badgeos-set-goals', false, dirname( $this->basename ) . '/languages' );

		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Include our other plugin files
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'init', array( $this, 'register_scripts_and_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'badgeos_set_goals_addon_script'));
		add_action( 'badgeos_settings', array( $this, 'badgeos_set_goals_settings' ) );
		add_action( 'badgeos_set_goals_task_hook', 'badgeos_set_goals_send_notifications');
	} /* __construct() */


	/**
	 * Include our plugin dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// If BadgeOS is available...
        if ( $this->meets_requirements() ) {
            require_once( $this->directory_path . '/includes/goals.php' );
            require_once( $this->directory_path . '/includes/ajax_functions.php' );
            require_once( $this->directory_path . '/includes/shortcodes/badgeos_goals_list.php' );
		}

	} /* includes() */

	/**
	 * Register all set goals scripts and styles
	 *
	 * @since  1.3.0
	 */
	function register_scripts_and_styles() {
		// Register scripts
        wp_register_script( 'badgeos-set-goals-achievements', $this->directory_url . '/js/badgeos-set-goals-achievements.js', array( 'jquery' ), '1.1.0', true );
		wp_register_style( 'badgeos-set-goals-front', $this->directory_url . '/css/badgeos-set-goals-front.css', null, '1.0.1' );
    }

    /**
     * Add filters 
     *
     * @since 1.0.0
     * @return null
     */
    function badgeos_set_goals_addon_script() {
    	wp_enqueue_script( 'badgeos-set-goals-achievements' );
	    wp_enqueue_style( 'badgeos-set-goals-front' );
	    
        global $user_ID;

    	$data = array(
    		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
    		'user_id'     => $user_ID,
    	);
    	wp_localize_script( 'badgeos-set-goals-achievements', 'badgeos_set_goals', $data );
    }

	/**
	 * Adds additional options to the BadgeOS Settings page
	 *
	 * @since 1.0.0
	 */
	public function badgeos_set_goals_settings( $settings ) {
		$goals_notification_custom_message  = $settings['goals_notification_custom_message'];
		$goals_emailing_no_goal             = $settings['goals_emailing_no_goal'];
		$goals_emailing_goals               = $settings['goals_emailing_goals'];
	?>
		<tr><td colspan="2"><hr/><h2><?php _e( 'Badgeos Goals Settings', 'badgeos' ); ?></h2></td></tr>
		<tr valign="top">
			<th scope="row">
				<label for="goals_emailing_goals"><?php _e( 'Emailing content for users with goals set: ', 'badgeos' ); ?></label>
			</th>
			<td>
				<textarea id="goals_emailing_goals" name="badgeos_settings[goals_emailing_goals]" cols="80" rows="10"><?php echo esc_textarea( $goals_emailing_goals ); ?></textarea>
			</td>
        </tr>
		<tr valign="top">
			<th scope="row">
				<label for="goals_emailing_no_goal"><?php _e( 'Emailing content for users without goal set: ', 'badgeos' ); ?></label>
			</th>
			<td>
				<textarea id="goals_emailing_no_goal" name="badgeos_settings[goals_emailing_no_goal]" cols="80" rows="10"><?php echo esc_textarea( $goals_emailing_no_goal ); ?></textarea>
			</td>
        </tr>
		<tr valign="top">
			<th scope="row">
				<label for="goals_notification_custom_message"><?php _e( 'Goals notification custom message: ', 'badgeos' ); ?></label>
			</th>
			<td>
				<textarea id="goals_notification_custom_message" name="badgeos_settings[goals_notification_custom_message]" cols="80" rows="10"><?php echo esc_textarea( $goals_notification_custom_message ); ?></textarea>
			</td>
        </tr>
		<tr valign="top">
			<th scope="row">
				<label for="Launch_set_goals_emailing"><?php _e( 'Launch the goals emailing: ', 'badgeos' ); ?></label>
			</th>
			<td>
                <input 	type="submit" class="button" 
				onclick="location.href='#<?php badgeos_set_goals_send_notifications(); ?>';" 
				value="<?php _e( 'Send now !', 'badgeos' ); ?>" />

			</td>
        </tr>
	<?php
	}


	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If BadgeOS is available, run our activation functions
		if ( $this->meets_requirements() ) {
			// Do some activation things
		}

	} /* activate() */

	/**
	 * Deactivation hook for the plugin.
	 *
	 * Note: this plugin may auto-deactivate due
	 * to $this->maybe_disable_plugin()
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		// Do some deactivation things.
	} /* deactivate() */

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') )
			return true;
		else
			return false;

	} /* meets_requirements() */

	/**
	 * Potentially output a custom error message and deactivate
	 * this plugin, if we don't meet requriements.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'BadgeOS Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-addon' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

} /* BadgeOS_Addon */

// Instantiate our class to a global variable that we can access elsewhere
$GLOBALS['badgeos_set_goals'] = new BadgeOS_Set_Goals();

function badgeos_set_goals_get_directory_path() {
	return $GLOBALS['badgeos_set_goals']->directory_path;
}
function badgeos_set_goals_get_directory_url() {
	return $GLOBALS['badgeos_set_goals']->directory_url;
}

