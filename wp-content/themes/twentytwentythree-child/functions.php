<?php
function register_custom_menus()
{
    // test
    register_nav_menus(
        array(
            'logged-in-menu' => __('Logged-in Menu', 'twentytwentythree-child'),
            'visitor-menu' => __('Visitor Menu', 'twentytwentythree-child'),
        )
    );
}
add_action('init', 'register_custom_menus');

function logged_in_out_menu_shortcode($atts, $content = null)
{
    if (is_user_logged_in()) {
        $menu = 'logged-in-menu';
    } else {
        $menu = 'visitor-menu';
    }
    return wp_nav_menu(
        array(
            'menu' => $menu,
            'echo' => false
        )
    );
}
add_shortcode('logged_in_out_menu', 'logged_in_out_menu_shortcode');

function um_profile_points()
{
    $user_id = get_current_user_id();
    // Get um_points for current user
    $um_points = get_user_meta($user_id, 'um_profile_points', true);

    // Calculate percentage of profile completeness
    $total_points = 100;
    $profile_completeness_percentage = round(($um_points / $total_points) * 100);

    // Output progress bar
    echo '<div class="um-profile-progress">';
    echo '<div class="um-profile-progress-bar" style="width: ' . $profile_completeness_percentage . '%;"></div>';
    echo '<div class="um-profile-progress-text">' . $profile_completeness_percentage . '% Complete . Fill in your profile to gain points!</div>';
    echo '</div>';
}

add_action('um_profile_header', 'um_profile_points', 10);

function check_profile()
{
    $um_points = 0;

    $user_id = get_current_user_id();

    $first_name = get_user_meta($user_id, 'first_name', true);
    if (!empty($first_name)) {
        $um_points += 10;
    }
    $last_name = get_user_meta($user_id, 'last_name', true);
    if (!empty($last_name)) {
        $um_points += 10;
    }
    $nickname = get_user_meta($user_id, 'nickname', true);
    if (!empty($nickname)) {
        $um_points += 10;
    }
    $user_bio = get_user_meta($user_id, 'description', true);
    if (!empty($user_bio)) {
        $um_points += 10;
    }
    $user_email = get_user_meta($user_id, 'user_email', true);
    if (!empty($user_email)) {
        $um_points += 10;
    }
    $university = get_user_meta($user_id, 'university', true);
    if (!empty($university)) {
        $um_points += 10;
    }
    $twitter = get_user_meta($user_id, 'twitter', true);
    if (!empty($twitter)) {
        $um_points += 10;
    }
    $linkedin = get_user_meta($user_id, 'linkedin', true);
    if (!empty($linkedin)) {
        $um_points += 10;
    }
    // Check if profile picture is set
    $profile_photo_path = WP_CONTENT_DIR . '/uploads/ultimatemember/' . $user_id . '/profile_photo.jpg';
    if (file_exists($profile_photo_path)) {
        $um_points += 10;
    }

    if ($um_points > 0) {
        update_user_meta($user_id, 'um_profile_points', $um_points);
    }
}

add_action('um_profile_update', 'check_profile', 10);

function first_time_register()
{
    $user_id = get_current_user_id();
    $user_roles = get_userdata($user_id)->roles;
    // Check if the user has the "Member" role
    if (in_array('Member', $user_roles)) {
        // Add the um_profile_points meta tag with a value of 0
        add_user_meta($user_id, 'um_profile_points', 0);
    }
    // Check if the user has completed their profile
    check_profile();
}
add_action('um_user_register', 'first_time_register', 10, 1);


function approved_tenants_callback()
{
    // Retrieve a list of users with the "um_tenant" role
    $users = get_users(array(
        'role' => 'um_tenant',
    ));

    // Loop through the list of users and retrieve the "user_status" value for each user
    $approved_tenants = array();
    foreach ($users as $user) {
        $user_id = $user->ID;
        $user_status = get_user_meta($user_id, 'user_status', true);
        if ($user_status === '0') {
            // The user is an approved tenant, so store their ID in the array
            $approved_tenants[] = $user_id;
        }
    }

    return $approved_tenants;
}
