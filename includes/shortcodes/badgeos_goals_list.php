<?php

/**
 * Register [badgeos_achievements_list] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_goals_list_shortcode() {

	// Setup a custom array of achievement types
	$achievement_types = array_diff( badgeos_get_achievement_types_slugs(), array( 'step' ) );
	array_unshift( $achievement_types, 'all' );

    // Setup a custom array of achievement tag
    $achievement_tags = get_terms('post_tag', 'fields=names&orderby=name');
	array_unshift( $achievement_tags, 'all' );

	badgeos_register_shortcode( array(
		'name'            => __( 'Goals', 'badgeos' ),
		'description'     => __( 'Output a list of goals.', 'badgeos' ),
		'slug'            => 'badgeos_goals_list',
		'output_callback' => 'badgeos_goals_list_shortcode',
		'attributes'      => array(
			'type' => array(
				'name'        => __( 'Achievement Type(s)', 'badgeos' ),
				'description' => __( 'Single, or comma-separated list of, achievement type(s) to display.', 'badgeos' ),
				'type'        => 'text',
				'values'      => $achievement_types,
				'default'     => 'all',
				),
			'limit' => array(
				'name'        => __( 'Limit', 'badgeos' ),
				'description' => __( 'Number of achievements to display.', 'badgeos' ),
				'type'        => 'text',
				'default'     => 10,
				),
			'show_filter' => array(
				'name'        => __( 'Show Filter', 'badgeos' ),
				'description' => __( 'Display filter controls.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
			'show_search' => array(
				'name'        => __( 'Show Search', 'badgeos' ),
				'description' => __( 'Display a search input.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
			'orderby' => array(
				'name'        => __( 'Order By', 'badgeos' ),
				'description' => __( 'Parameter to use for sorting.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'menu_order' => __( 'Menu Order', 'badgeos' ),
					'ID'         => __( 'Achievement ID', 'badgeos' ),
					'title'      => __( 'Achievement Title', 'badgeos' ),
					'date'       => __( 'Published Date', 'badgeos' ),
					'modified'   => __( 'Last Modified Date', 'badgeos' ),
					'author'     => __( 'Achievement Author', 'badgeos' ),
					'rand'       => __( 'Random', 'badgeos' ),
					),
				'default'     => 'menu_order',
				),
			'order' => array(
				'name'        => __( 'Order', 'badgeos' ),
				'description' => __( 'Sort order.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array( 'ASC' => __( 'Ascending', 'badgeos' ), 'DESC' => __( 'Descending', 'badgeos' ) ),
				'default'     => 'ASC',
				),
			'user_id' => array(
				'name'        => __( 'User ID', 'badgeos' ),
				'description' => __( 'Show only achievements earned by a specific user.', 'badgeos' ),
				'type'        => 'text',
				),
			'include' => array(
				'name'        => __( 'Include', 'badgeos' ),
				'description' => __( 'Comma-separated list of specific achievement IDs to include.', 'badgeos' ),
				'type'        => 'text',
				),
			'exclude' => array(
				'name'        => __( 'Exclude', 'badgeos' ),
				'description' => __( 'Comma-separated list of specific achievement IDs to exclude.', 'badgeos' ),
				'type'        => 'text',
				),
			'wpms' => array(
				'name'        => __( 'Include Multisite Achievements', 'badgeos' ),
				'description' => __( 'Show achievements from all network sites.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'false',
				),
			'layout' => array(
				'name'        => __( 'Layout', 'badgeos' ),
				'description' => __( 'Achievements layout', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'grid' => __('Grid', 'badgeos'),
                    'list' => __('List', 'badgeos'),
                    ),
                'default'     => 'list',
 				),
            'tag' => array(
				'name'        => __( 'Achievement Tag(s)', 'badgeos' ),
				'description' => __( 'Single, or comma-separated list of, achievement tag(s) to display.', 'badgeos' ),
				'type'        => 'text',
				'values'      => $achievement_tags,
                'default'     => 'all',
                ),
            'show_goals' => array(
                'name'       => __( 'Show Goals', 'badgeos' ),
                'description'=> __( 'Display only goals', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'True',
			    ),
		),
	) );
}

/**
 * Achievement List Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_goals_list_shortcode( $atts = array () ){

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_goals_list'] ) )
		return '';

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'show_filter' => true,
		'show_search' => true,
		'group_id'    => '0',
		'user_id'     => '0',
		'wpms'        => false,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
		'include'     => array(),
		'exclude'     => array(),
		'meta_key'    => '',
        'meta_value'  => '',
        'layout'      => 'list',
		'tag'         => 'all',
        'show_goals'  => 'false',
	), $atts, 'badgeos_goals_list' ) );

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );
	wp_enqueue_script( 'badgeos-set-goals-achievements' );

	$data = array(
		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		'type'        => $type,
		'limit'       => $limit,
		'show_filter' => $show_filter,
		'show_search' => $show_search,
		'group_id'    => $group_id,
		'user_id'     => $user_id,
		'wpms'        => $wpms,
		'orderby'     => $orderby,
		'order'       => $order,
		'include'     => $include,
		'exclude'     => $exclude,
		'meta_key'    => $meta_key,
        'meta_value'  => $meta_value,
        'layout'      => $layout,
		'tag'         => $tag,
        'show_goals'  => $show_goals,
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	// If we're dealing with multiple achievement types
	if ( 'all' == $type ) {
		$post_type_plural = __( 'achievements', 'badgeos' );
	} else {
		$types = explode( ',', $type );
		$post_type_plural = ( 1 == count( $types ) ) ? get_post_type_object( $type )->labels->name : __( 'achievements', 'badgeos' );
	}

	$badges = '';

	$badges .= '<div id="badgeos-achievements-filters-wrap">';
		// Filter
		if ( $show_filter == 'false' ) {

			$filter_value = 'all';
			if( $user_id ){
				$filter_value = 'completed';
				$badges .= '<input type="hidden" name="user_id" id="user_id" value="'.$user_id.'">';
			}
			$badges .= '<input type="hidden" name="achievements_list_filter" id="goals_list_filter" value="'.$filter_value.'">';

		}else{

			$badges .= '<div id="badgeos-achievements-filter">';

				$badges .= __( 'Filter:', 'badgeos' ) . '<select name="achievements_list_filter" id="goals_list_filter">';

					$badges .= '<option value="all">' . sprintf( __( 'All %s', 'badgeos' ), $post_type_plural );
					// If logged in
					if ( $user_ID >0 ) {
						$badges .= '<option value="completed">' . sprintf( __( 'Completed %s', 'badgeos' ), $post_type_plural );
						$badges .= '<option value="not-completed">' . sprintf( __( 'Not Completed %s', 'badgeos' ), $post_type_plural );
					}
					// TODO: if show_points is true "Badges by Points"
					// TODO: if dev adds a custom taxonomy to this post type then load all of the terms to filter by

				$badges .= '</select>';

			$badges .= '</div>';

		}

		// Search
		if ( $show_search != 'false' ) {

			$search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
			$badges .= '<div id="badgeos-achievements-search">';
				$badges .= '<form id="goals_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
				$badges .= sprintf( __( 'Search: %s', 'badgeos' ), '<input type="text" id="achievements_list_search" name="achievements_list_search" value="'. $search .'">' );
				$badges .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="' . esc_attr__( 'Go', 'badgeos' ) . '">';
				$badges .= '</form>';
			$badges .= '</div>';

		}

	$badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

	// Content Container
    $badges .= '<div id="badgeos-achievements-container"></div>';

	// Hidden fields and Load More button
	$badges .= '<input type="hidden" id="badgeos_achievements_offset" value="0">';
	$badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
	$badges .= '<input type="button" id="goals_list_load_more" value="' . esc_attr__( 'Load More', 'badgeos' ) . '" style="display:none;">';
	$badges .= '<div class="badgeos-spinner"></div>';

	// Reset Post Data
	wp_reset_postdata();

	// Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_goals_list'] = true;
	return $badges;

}
